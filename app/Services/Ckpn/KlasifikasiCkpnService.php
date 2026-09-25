<?php

namespace App\Services\Ckpn;

use App\Enums\TipeCkpn;
use App\Models\CkpnKlasifikasi;
use App\Models\CkpnPeriode;
use App\Models\HistoryPembiayaan;
use App\Models\SetupParameter;
use Illuminate\Support\Facades\DB;

/**
 * Menentukan klasifikasi CKPN Individual/Kolektif per periode.
 *
 * Aturan (issue #2):
 * - Dari seluruh rekening NPF (col 3/4/5), hanya TOP N outstanding terbesar
 *   yang menjadi CKPN Individual.
 * - NPF peringkat N+1 ke bawah dan kol 1-2 menjadi CKPN Kolektif.
 */
class KlasifikasiCkpnService
{
    /**
     * Hitung ulang klasifikasi untuk satu periode.
     *
     * @return array{individual: int, kolektif: int}
     */
    public function klasifikasikan(string $periode): array
    {
        $this->assertPeriodeEditable($periode);

        $limit = max(1, (int) SetupParameter::get('ckpn.individual_maks_rekening', 10));

        // Semua history aktif periode, dengan data pembiayaan terkait.
        $histories = HistoryPembiayaan::query()
            ->with('pembiayaan')
            ->where('periode', $periode)
            ->where(fn ($q) => $q->whereNull('stsacc')->orWhere('stsacc', '!=', 'W'))
            ->get();

        // Kandidat individual: NPF (col 3/4/5) diurut outstanding menurun.
        $npfSorted = $histories
            ->filter(fn (HistoryPembiayaan $h): bool => (int) $h->col >= 3)
            ->sortByDesc(fn (HistoryPembiayaan $h): float => $h->bakiDebet())
            ->values();

        $topN = $npfSorted->take($limit)->keyBy('nokontrak');

        // Nilai individual manual yang sudah terisi tetap dipertahankan.
        $manualNilai = CkpnKlasifikasi::query()
            ->where('periode', $periode)
            ->where('nilai_individual', '>', 0)
            ->pluck('nilai_individual', 'nokontrak')
            ->all();

        CkpnKlasifikasi::query()->where('periode', $periode)->delete();

        $now = now();
        $payload = [];

        foreach ($histories as $history) {
            $isIndividual = $topN->has($history->nokontrak);

            $payload[] = [
                'periode' => $periode,
                'nokontrak' => $history->nokontrak,
                'tipe' => $isIndividual ? TipeCkpn::Individual->value : TipeCkpn::Kolektif->value,
                'alasan' => $isIndividual
                    ? 'TOP '.min($limit, $npfSorted->count()).' NPF outstanding terbesar'
                    : ($history->col >= 3 ? 'NPF di luar TOP outstanding' : 'Kolektibilitas 1-2'),
                'metode_individual' => $isIndividual ? 'top_n_npf' : null,
                'nilai_individual' => $manualNilai[$history->nokontrak] ?? 0,
                'ditentukan_oleh' => auth()->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Bulk insert per batch agar hemat memori.
            if (count($payload) >= 500) {
                DB::table('ckpn_klasifikasi')->insert($payload);
                $payload = [];
            }
        }

        if ($payload !== []) {
            DB::table('ckpn_klasifikasi')->insert($payload);
        }

        return [
            'individual' => $topN->count(),
            'kolektif' => $histories->count() - $topN->count(),
        ];
    }

    /**
     * Override manual satu rekening menjadi individual/kolektif.
     */
    public function override(string $periode, string $nokontrak, TipeCkpn $tipe, ?float $nilaiIndividual = null): void
    {
        $this->assertPeriodeEditable($periode);

        $attributes = [
            'tipe' => $tipe,
            'alasan' => 'Override manual',
            'metode_individual' => null,
            'ditentukan_oleh' => auth()->id(),
        ];

        if ($nilaiIndividual !== null) {
            $attributes['nilai_individual'] = $nilaiIndividual;
        }

        CkpnKlasifikasi::query()->updateOrCreate(
            ['periode' => $periode, 'nokontrak' => $nokontrak],
            $attributes,
        );
    }

    private function assertPeriodeEditable(string $periode): void
    {
        $periodeRecord = CkpnPeriode::query()->where('periode', $periode)->first();

        if ($periodeRecord !== null && $periodeRecord->isLocked()) {
            abort(422, "Periode {$periode} sudah terkunci atau final.");
        }
    }
}
