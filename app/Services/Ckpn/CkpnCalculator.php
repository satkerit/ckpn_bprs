<?php

namespace App\Services\Ckpn;

use App\Enums\DimensiSegmentasi;
use App\Enums\TipeCkpn;
use App\Models\CkpnHasil;
use App\Models\CkpnKlasifikasi;
use App\Models\CkpnLgdParameter;
use App\Models\CkpnPdNetflow;
use App\Models\CkpnRun;
use App\Models\HistoryPembiayaan;
use App\Models\Pembiayaan;
use Illuminate\Support\Collection;

class CkpnCalculator
{
    public function __construct(
        private readonly EadCalculator $eadCalculator,
        private readonly BucketClassifier $bucketClassifier,
        private readonly LgdCalculator $lgdCalculator,
        private readonly AkadRoleResolver $akadRoleResolver,
    ) {}

    /**
     * Jalankan kalkulasi CKPN penuh untuk satu CkpnRun.
     *
     * Proses ini mengambil seluruh data pembiayaan aktif, mencocokkan dengan
     * parameter PD Netflow dan klasifikasi kolektif/individual, lalu menyimpan
     * hasilnya ke tabel ckpn_hasil dan memperbarui ringkasan di CkpnRun.
     *
     * @param  CkpnRun  $run  Run CKPN yang sedang diproses (status harus 'running').
     * @return array{total_ead: float, total_ckpn: float, total_ppka: float, cadangan_ppka_tambahan: float, jumlah_hasil: int}
     *                                                                                                                         Ringkasan hasil kalkulasi: total EAD, CKPN, PPKA, cadangan tambahan, dan jumlah baris hasil.
     */
    public function calculateRun(CkpnRun $run): array
    {
        // Preload semua lookup table sebelum chunk loop – eliminasi N+1 dan persiap kunci komposit O(1).
        $pdRows = CkpnPdNetflow::query()->where('ckpn_run_id', $run->id)->get()
            ->keyBy(fn ($row): string => $row->segment_key.'|'.$row->bucket_asal);
        $klasifikasi = CkpnKlasifikasi::query()->where('periode', $run->periode)->get()->keyBy('nokontrak');
        $lgdParameters = CkpnLgdParameter::query()->where('periode', $run->periode)->get();
        $dimensions = $run->segments()->orderBy('urutan')->pluck('dimensi')->all() ?: array_column(DimensiSegmentasi::cases(), 'value');

        // Accumulator counters – updated chunk-by-chunk.
        $totalEad = 0.0;
        $totalCkpn = 0.0;
        $totalPpka = 0.0;
        $jumlahHasil = 0;

        // Delete previous results once before chunking.
        CkpnHasil::query()->where('ckpn_run_id', $run->id)->delete();

        // Fix N+1: Filter histories hanya untuk periode yang dibutuhkan di eager load,
        // bukan load semua periode lalu filter di memori.
        Pembiayaan::query()
            ->with([
                'histories' => fn ($q) => $q->where('periode', $run->periode),
                'agunan',
            ])
            ->chunkById(200, function (Collection $pembiayaanChunk) use (
                $run, $pdRows, $klasifikasi, $lgdParameters, $dimensions,
                &$totalEad, &$totalCkpn, &$totalPpka, &$jumlahHasil
            ): void {
                $hasil = [];

                foreach ($pembiayaanChunk as $item) {
                    // histories hanya berisi record untuk periode ini (eager load sudah difilter)
                    $history = $item->histories->first();
                    if ($history === null) {
                        continue;
                    }

                    if (! $history->isAktif() || $history->isWriteoff()) {
                        continue;
                    }

                    $segmentKey = $this->segment($item, $dimensions);
                    $bucket = $this->bucketClassifier->classify($history->haritgk, $history->stsacc, $history->isWriteoff());
                    $pd = (float) ($pdRows->get($segmentKey.'|'.$bucket)?->pd_kumulatif ?? 0);
                    $ead = $this->eadCalculator->calculate($item, $history);
                    $inScope = $this->inScope($item, $history);
                    $classification = $klasifikasi->get($item->nokontrak);
                    $individual = $classification?->tipe === TipeCkpn::Individual;

                    // Fix: Gunakan LgdCalculator (dengan haircut & biaya lelang) alih-alih
                    // kalkulasi LGD naif berbasis nominal agunan langsung.
                    $lgd = $this->lgdCalculator->lgdPercentForItem($item, $ead, $segmentKey, $lgdParameters);

                    $calculation = $individual && $classification?->nilai_individual !== null
                        ? $this->individualCalculation((float) $classification->nilai_individual, (float) $history->ppka)
                        : $this->calculate($inScope ? $ead : 0, $inScope ? $pd : 0, $inScope ? $lgd : 0, (float) $history->ppka);

                    $hasil[] = [
                        'ckpn_run_id' => $run->id,
                        'periode' => $run->periode,
                        'nokontrak' => $item->nokontrak,
                        'nocif' => $item->nocif,
                        'nama' => $item->nama,
                        'kdloc' => $item->kdloc,
                        'pokpby' => $item->pokpby,
                        'kdprd' => $item->kdprd,
                        'gunadeb' => $item->gunadeb,
                        'segment_key' => $segmentKey,
                        'tipe_ckpn' => $individual ? 'individual' : 'kolektif',
                        'in_scope_psak414' => $inScope,
                        'bucket' => $bucket,
                        'ead' => $ead,
                        'pd_netflow' => $pd,
                        'lgd_persen' => $lgd,
                        'ckpn_psak414' => $calculation['ckpn'],
                        'ppka_wajib' => (float) $history->ppka,
                        'selisih' => $calculation['selisih'],
                        'cadangan_tambahan' => $calculation['cadangan_tambahan'],
                    ];
                }

                if ($hasil !== []) {
                    CkpnHasil::query()->insert($hasil);

                    // Accumulate totals from this chunk.
                    $totalEad += (float) array_sum(array_column($hasil, 'ead'));
                    $totalCkpn += (float) array_sum(array_column($hasil, 'ckpn_psak414'));
                    $totalPpka += (float) array_sum(array_column($hasil, 'ppka_wajib'));
                    $jumlahHasil += count($hasil);
                }
            });

        $additional = max(0.0, $totalPpka - $totalCkpn);

        $run->update([
            'total_ead' => $totalEad,
            'total_ckpn' => $totalCkpn,
            'total_ppka' => $totalPpka,
            'cadangan_ppka_tambahan' => $additional,
        ]);

        return [
            'total_ead' => round($totalEad, 2),
            'total_ckpn' => round($totalCkpn, 2),
            'total_ppka' => round($totalPpka, 2),
            'cadangan_ppka_tambahan' => round($additional, 2),
            'jumlah_hasil' => $jumlahHasil,
        ];
    }

    /** @return array{ckpn: float, selisih: float, cadangan_tambahan: float} */
    public function calculate(float $ead, float $pdNetflow, float $lgdPersen, float $ppkaWajib = 0): array
    {
        $ckpn = round(max(0.0, $ead) * min(1.0, max(0.0, $pdNetflow)) * min(1.0, max(0.0, $lgdPersen)), 2);
        $selisih = round($ckpn - max(0.0, $ppkaWajib), 2);

        return ['ckpn' => $ckpn, 'selisih' => $selisih, 'cadangan_tambahan' => round(max(0.0, -$selisih), 2)];
    }

    private function segment(Pembiayaan $item, array $dimensions): string
    {
        $attributes = [
            'kdloc' => $item->kdloc,
            'pokpby' => $item->pokpby,
            'gunadeb' => $item->gunadeb,
            'kdprd' => $item->kdprd,
        ];

        return collect($dimensions)->map(fn (string $dimension): string => $dimension.'='.trim((string) ($attributes[$dimension] ?? '')))->implode('|');
    }

    private function individualCalculation(float $nilaiIndividual, float $ppkaWajib): array
    {
        $ckpn = round(max(0.0, $nilaiIndividual), 2);
        $selisih = round($ckpn - max(0.0, $ppkaWajib), 2);

        return ['ckpn' => $ckpn, 'selisih' => $selisih, 'cadangan_tambahan' => round(max(0.0, -$selisih), 2)];
    }

    private function inScope(Pembiayaan $item, HistoryPembiayaan $history): bool
    {
        return $this->akadRoleResolver->masukPerhitungan($item->pokpby, $history);
    }
}
