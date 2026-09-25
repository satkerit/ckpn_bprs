<?php

namespace App\Services\Ckpn;

use App\Enums\MetodeLgd;
use App\Models\Agunan;
use App\Models\CkpnLgdAgunanLikuidasi;
use App\Models\CkpnLgdParameter;
use App\Models\HistoryPembiayaan;
use App\Models\Pembiayaan;
use Illuminate\Support\Collection;

class LgdCalculator
{
    public function __construct(private readonly EadCalculator $eadCalculator) {}

    /**
     * Hitung parameter LGD (Loss Given Default) untuk satu segmen/periode.
     *
     * Menyaring debitur yang memenuhi kriteria default, menghitung EAD agregat,
     * lalu menghitung recovery dari agunan sesuai metode yang dipilih.
     *
     * @param  Collection<int, Pembiayaan>  $pembiayaan  Koleksi pembiayaan dengan relasi histories dan agunan sudah di-load.
     * @param  string  $periode  Periode laporan format YYYYMM (contoh: '202609').
     * @param  string  $segmentKey  Kunci segmentasi untuk filter parameter agunan (kosong = semua segmen).
     * @param  string|null  $jnsjamin  Jenis jaminan untuk filter agunan (null = semua jenis).
     * @param  MetodeLgd  $metode  Metode LGD: Shortfall (collateral shortfall) atau ExpectedRecovery (historis likuidasi).
     * @return array{total_ead_default: float, total_penerimaan: float, shortfall: float, lgd_persen: float, jumlah_debitur_default: int}
     *                                                                                                                                    Ringkasan LGD: EAD debitur default, total recovery, shortfall, persentase LGD, dan jumlah debitur.
     */
    public function calculate(Collection $pembiayaan, string $periode, string $segmentKey = '', ?string $jnsjamin = null, MetodeLgd $metode = MetodeLgd::Shortfall): array
    {
        // Preload ALL LGD parameters for this periode once – eliminates N+1.
        $allParameters = CkpnLgdParameter::query()->where('periode', $periode)->get();

        // P1 FIX: Preload all liquidation records for all nokontrak in this batch to eliminate N+1 queries.
        $allLikuidasi = [];
        if ($metode === MetodeLgd::ExpectedRecovery) {
            $lookbackBulan = max(1, (int) config('ckpn.perhitungan.lgd_lookback_bulan', 60));
            $batasAwal = $this->kurangiBulan($periode, $lookbackBulan);
            $nokontraks = $pembiayaan->pluck('nokontrak')->unique()->values()->all();

            if (! empty($nokontraks)) {
                $allLikuidasi = CkpnLgdAgunanLikuidasi::query()
                    ->whereIn('nokontrak', $nokontraks)
                    ->where('periode', '<=', $periode)
                    ->where('periode', '>=', $batasAwal)
                    ->get()
                    ->groupBy('nokontrak'); // keyBy nokontrak for O(1) lookup
            }
        }

        $defaults = $pembiayaan->filter(function (Pembiayaan $item) use ($periode): bool {
            $history = $item->histories->firstWhere('periode', $periode);

            return $history instanceof HistoryPembiayaan && $this->isDefault($history);
        });
        $ead = $defaults->sum(function (Pembiayaan $item) use ($periode): float {
            return $this->eadCalculator->calculate($item, $item->histories->firstWhere('periode', $periode));
        });
        $recovery = $defaults->sum(fn (Pembiayaan $item): float => $this->recovery($item, $periode, $segmentKey, $jnsjamin, $metode, $allParameters, $allLikuidasi));
        $shortfall = max(0.0, $ead - $recovery);

        return [
            'total_ead_default' => round($ead, 2),
            'total_penerimaan' => round($recovery, 2),
            'shortfall' => round($shortfall, 2),
            'lgd_persen' => $ead > 0 ? round(min(1.0, $shortfall / $ead), 6) : 0.0,
            'jumlah_debitur_default' => $defaults->count(),
        ];
    }

    private function isDefault(HistoryPembiayaan $history): bool
    {
        return (int) ($history->haritgk ?? 0) >= (int) config('ckpn.perhitungan.haritgk_default', 90)
            || (int) ($history->col ?? 0) >= (int) config('ckpn.perhitungan.col_default', 3);
    }

    /**
     * Hitung nilai recovery (penerimaan) dari agunan untuk satu debitur default.
     *
     * Pada metode Shortfall: nilai recovery = jumlah nilai likuidasi agunan setelah haircut dan biaya lelang.
     * Pada metode ExpectedRecovery: nilai recovery = realisasi/estimasi historis likuidasi agunan (capped EAD write-off).
     *
     * @param  Pembiayaan  $pembiayaan  Data pembiayaan beserta relasi agunan.
     * @param  string  $periode  Periode laporan format YYYYMM.
     * @param  string  $segmentKey  Kunci segmentasi untuk lookup parameter haircut/biaya.
     * @param  string|null  $jnsjamin  Filter jenis jaminan (null = semua).
     * @param  MetodeLgd  $metode  Metode perhitungan recovery.
     * @param  Collection  $allParameters  Koleksi parameter LGD (haircut, biaya lelang) yang sudah di-load.
     * @return float Nilai recovery dalam nominal rupiah.
     */
    private function recovery(Pembiayaan $pembiayaan, string $periode, string $segmentKey, ?string $jnsjamin, MetodeLgd $metode, Collection $allParameters, array $allLikuidasi = []): float
    {
        $recovery = $pembiayaan->agunan
            ->when($jnsjamin !== null, fn (Collection $items): Collection => $items->where('jnsjamin', $jnsjamin))
            ->sum(fn (Agunan $agunan): float => $this->collateralRecovery($agunan, $segmentKey, $jnsjamin, $allParameters));

        if ($metode === MetodeLgd::ExpectedRecovery) {
            return $this->expectedRecovery($pembiayaan, $periode, $allLikuidasi);
        }

        return $recovery;
    }

    /**
     * Expected Recovery berbasis pengalaman likuidasi historis:
     * recovery neto = realisasi/estimasi agunan dikurangi biaya terkait,
     * dibatasi (capped) sebesar outstanding saat write-off.
     *
     * P1 FIX: Menggunakan preloaded likuidasi collection (grouped by nokontrak)
     * untuk menghindari query tambahan di dalam loop.
     */
    private function expectedRecovery(Pembiayaan $pembiayaan, string $periode, array $allLikuidasi = []): float
    {
        // P1 FIX: Lookup dari preloaded collection instead of querying.
        $likuidasi = collect($allLikuidasi[$pembiayaan->nokontrak] ?? []);

        if ($likuidasi->isEmpty()) {
            return 0.0;
        }

        $outstandingWriteoff = $this->outstandingWriteoff($pembiayaan, $periode);
        $neto = $likuidasi->sum(function (CkpnLgdAgunanLikuidasi $item): float {
            $hasil = max((float) $item->nilai_estimasi, (float) $item->nilai_eksekusi);

            return max(0.0, $hasil - max(0.0, (float) $item->biaya_terkait));
        });

        return $outstandingWriteoff > 0 ? min($neto, $outstandingWriteoff) : $neto;
    }

    /**
     * Outstanding (baki debet) pada snapshot write-off terakhir sebelum/sama dengan periode laporan.
     */
    private function outstandingWriteoff(Pembiayaan $pembiayaan, string $periode): float
    {
        $writeoff = $pembiayaan->histories
            ->filter(fn (HistoryPembiayaan $history): bool => $history->periode <= $periode && $history->isWriteoff())
            ->sortBy('periode')
            ->last();

        return $writeoff instanceof HistoryPembiayaan ? max(0.0, $writeoff->bakiDebet()) : 0.0;
    }

    /** Mundur sejumlah bulan dari periode format YYYYMM. */
    private function kurangiBulan(string $periode, int $bulan): string
    {
        $tahun = (int) substr($periode, 0, 4);
        $bulanKe = (int) substr($periode, 4, 2) - $bulan;

        while ($bulanKe < 1) {
            $bulanKe += 12;
            $tahun--;
        }

        return sprintf('%04d%02d', $tahun, $bulanKe);
    }

    /**
     * Hitung persentase LGD per item pembiayaan berdasarkan agunan yang dimiliki.
     *
     * Digunakan oleh CkpnCalculator untuk menghitung LGD kolektif per rekening.
     * Parameter LGD diambil dari koleksi yang sudah di-preload untuk efisiensi.
     *
     * @param  Pembiayaan  $item  Data pembiayaan beserta relasi agunan yang sudah di-load.
     * @param  float  $ead  Exposure at Default rekening ini.
     * @param  string  $segmentKey  Kunci segmentasi untuk lookup parameter haircut/biaya.
     * @param  Collection  $lgdParameters  Koleksi CkpnLgdParameter yang sudah di-preload.
     * @return float Persentase LGD antara 0.0 dan 1.0.
     */
    public function lgdPercentForItem(Pembiayaan $item, float $ead, string $segmentKey, Collection $lgdParameters): float
    {
        if ($ead <= 0) {
            return 0.0;
        }

        $totalRecovery = $item->agunan->sum(
            fn (Agunan $agunan): float => $this->collateralRecovery($agunan, $segmentKey, null, $lgdParameters)
        );

        return min(1.0, max(0.0, ($ead - min($ead, $totalRecovery)) / $ead));
    }

    private function collateralRecovery(Agunan $agunan, string $segmentKey, ?string $jnsjamin, Collection $allParameters): float
    {
        // Filter from the preloaded collection – no additional DB queries.
        $parameter = $allParameters
            ->filter(fn ($p): bool => ($p->segment_key === $segmentKey || $p->segment_key === null)
                && ($p->jnsjamin === $jnsjamin || $p->jnsjamin === $agunan->jnsjamin || $p->jnsjamin === null))
            ->sortBy([
                fn ($a, $b): int => ($a->segment_key === null ? 1 : 0) <=> ($b->segment_key === null ? 1 : 0),
                fn ($a, $b): int => ($a->jnsjamin === null ? 1 : 0) <=> ($b->jnsjamin === null ? 1 : 0),
            ])
            ->first();

        $haircut = min(100.0, max(0.0, (float) ($parameter?->haircut_persen ?? 0)));
        $fee = min(100.0, max(0.0, (float) ($parameter?->biaya_lelang_persen ?? 0)));

        return max(0.0, (float) $agunan->nominallikuid * (1 - $haircut / 100) * (1 - $fee / 100));
    }
}
