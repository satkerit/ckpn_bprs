<?php

namespace App\Services\Ckpn;

use App\Models\CkpnRollRate;
use App\Models\SetupParameter;
use Illuminate\Support\Collection;

/**
 * Perhitungan PD kolektif dari matriks transisi bulanan.
 *
 * Digunakan oleh kedua metode yang tersedia di sistem: PD Netflow (roll rate)
 * dan PD Migration. Keduanya memakai matriks transisi yang sama, sehingga
 * hasilnya identik untuk lookback dan segmentasi yang sama.
 *
 * Tahapan perhitungan sesuai docs/catatan.md:
 *  1. Susun matriks transisi bulanan P(bucket_asal → bucket_tujuan) untuk
 *     setiap pasangan periode T(t-1) → T(t).
 *  2. Rata-ratakan N matriks tersebut secara aritmetik:
 *     PD Netflow Average = (Matriks_1 + ... + Matriks_N) / N
 *  3. Jalankan rantai Markov pada matriks rata-rata itu sebanyak N langkah.
 *     PD 1 bulan = massa probabilitas di bucket loss setelah langkah pertama.
 *     PD kumulatif = massa di bucket loss setelah N langkah.
 */
class PdCalculator
{
    /**
     * @param  Collection<int, CkpnRollRate>  $rollRates
     * @return array<int, array{segment_key: string, bucket_asal: string, pd_1_bulan: float, pd_kumulatif: float, netflow_to_loss: float}>
     */
    public function calculate(Collection $rollRates, ?string $lossBucket = null): array
    {
        if ($rollRates->isEmpty()) {
            return [];
        }

        $lossBuckets = $this->lossBuckets($lossBucket);
        $result = [];

        foreach ($rollRates->groupBy('segment_key') as $segmentKey => $segmentRows) {
            /** @var Collection<int, CkpnRollRate> $segmentRows */
            $periods = $segmentRows->pluck('periode_asal')->unique()->sort()->values();

            if ($periods->isEmpty()) {
                continue;
            }

            // Matriks rata-rata per bucket asal: [bucket_asal][bucket_tujuan] => probabilitas.
            $average = $this->averageMatrices($segmentRows, $periods);

            foreach ($segmentRows->pluck('bucket_asal')->map(fn ($b): string => (string) $b)->unique() as $bucketAsal) {
                $result[] = $this->walk($average, $bucketAsal, $periods, $lossBuckets, (string) $segmentKey);
            }
        }

        return $result;
    }

    /**
     * Rata-rata aritmetik matriks transisi seluruh periode pada rentang observasi.
     *
     * Setiap periode dinormalisasi lebih dulu menjadi matriks transisi
     * P(bucket_asal → bucket_tujuan), lalu dijumlahkan dan dibagi jumlah
     * periode yang punya pengamatan untuk bucket asal tersebut.
     * Periode tanpa data tidak ikut menghitung penyebut.
     *
     * @param  Collection<int, CkpnRollRate>  $segmentRows
     * @param  Collection<int, string>  $periods
     * @return array<string, array<string, float>>
     */
    private function averageMatrices(Collection $segmentRows, Collection $periods): array
    {
        $basis = (string) (SetupParameter::get('ckpn.basis_pd')
            ?? config('ckpn.perhitungan.basis_pd_default', 'debitur'));

        $sum = [];
        $jumlahPeriode = [];

        foreach ($periods as $period) {
            $matrix = [];

            foreach ($segmentRows->where('periode_asal', $period) as $row) {
                $asal = (string) $row->bucket_asal;
                $tujuan = (string) $row->bucket_tujuan;
                $matrix[$asal][$tujuan] = ($matrix[$asal][$tujuan] ?? 0.0) + $this->bobot($row, $basis);
            }

            foreach ($matrix as $asal => $destinations) {
                $total = array_sum($destinations);

                if ($total <= 0.0) {
                    continue;
                }

                $jumlahPeriode[$asal] = ($jumlahPeriode[$asal] ?? 0) + 1;

                foreach ($destinations as $tujuan => $bobot) {
                    $sum[$asal][$tujuan] = ($sum[$asal][$tujuan] ?? 0.0) + ($bobot / $total);
                }
            }
        }

        foreach ($sum as $asal => $destinations) {
            foreach ($destinations as $tujuan => $total) {
                $sum[$asal][$tujuan] = $total / $jumlahPeriode[$asal];
            }
        }

        return $sum;
    }

    /**
     * Jalankan rantai Markov pada matriks rata-rata sepanjang N langkah.
     *
     * @param  array<string, array<string, float>>  $average
     * @param  Collection<int, string>  $periods
     * @param  array<int, string>  $lossBuckets
     * @return array{segment_key: string, bucket_asal: string, pd_1_bulan: float, pd_kumulatif: float, netflow_to_loss: float}
     */
    private function walk(array $average, string $bucketAsal, Collection $periods, array $lossBuckets, string $segmentKey): array
    {
        $state = [$bucketAsal => 1.0];
        $pdOneMonth = 0.0;

        foreach ($periods as $index => $period) {
            $next = [];

            foreach ($state as $bucket => $weight) {
                // Loss state bersifat absorbing: probabilitas yang sudah
                // mencapai WO tidak keluar lagi dan terus terakumulasi.
                if (in_array($bucket, $lossBuckets, true)) {
                    $next[$bucket] = ($next[$bucket] ?? 0.0) + $weight;

                    continue;
                }

                $probabilities = $average[$bucket] ?? [];

                // Tanpa data transisi, asumsi debitur tetap di bucket yang sama.
                if ($probabilities === []) {
                    $next[$bucket] = ($next[$bucket] ?? 0.0) + $weight;

                    continue;
                }

                foreach ($probabilities as $tujuan => $probability) {
                    $next[$tujuan] = ($next[$tujuan] ?? 0.0) + ($weight * $probability);
                }
            }

            $state = $next;

            if ($index === 0) {
                $pdOneMonth = $this->lossMass($state, $lossBuckets);
            }
        }

        $cumulative = $this->lossMass($state, $lossBuckets);

        return [
            'segment_key' => $segmentKey,
            'bucket_asal' => $bucketAsal,
            'pd_1_bulan' => round(min(1.0, max(0.0, $pdOneMonth)), 6),
            'pd_kumulatif' => round(min(1.0, max(0.0, $cumulative)), 6),
            'netflow_to_loss' => round(min(1.0, max(0.0, $cumulative)), 6),
        ];
    }

    /** Pembagi sesuai basis_pd: jumlah debitur atau saldo outstanding. */
    private function bobot(CkpnRollRate $row, string $basis): float
    {
        return $basis === 'saldo'
            ? max(0.0, (float) $row->total_saldo)
            : (float) $row->jumlah_rekening;
    }

    /**
     * @param  array<string, float>  $state
     * @param  array<int, string>  $lossBuckets
     */
    private function lossMass(array $state, array $lossBuckets): float
    {
        return array_sum(array_intersect_key($state, array_flip($lossBuckets)));
    }

    /**
     * @return array<int, string>
     */
    private function lossBuckets(?string $lossBucket): array
    {
        if ($lossBucket !== null) {
            return [$lossBucket];
        }

        return array_map('strval', (array) config('ckpn.perhitungan.bucket_loss', ['WO']));
    }
}
