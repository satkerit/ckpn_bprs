<?php

namespace App\Services\Ckpn;

use App\Models\CkpnRollRate;
use Illuminate\Support\Collection;

class PdNetflowCalculator
{
    /**
     * @param  Collection<int, CkpnRollRate>  $rollRates
     * @return array<int, array{segment_key: string, bucket_asal: string, pd_1_bulan: float, pd_kumulatif: float, netflow_to_loss: float}>
     */
    public function calculate(Collection $rollRates, ?string $lossBucket = null): array
    {
        $lossBuckets = $lossBucket === null
            ? (app()->bound('config') ? config('ckpn.perhitungan.bucket_loss', ['WO']) : ['WO'])
            : [$lossBucket];
        $lossBuckets = array_map('strval', (array) $lossBuckets);
        $result = [];

        foreach ($rollRates->groupBy(fn (CkpnRollRate $row): string => json_encode([
            (string) $row->segment_key,
            (string) $row->bucket_asal,
        ], JSON_THROW_ON_ERROR)) as $rows) {
            $segmentKey = (string) $rows->first()->segment_key;
            $bucketAsal = (string) $rows->first()->bucket_asal;
            $periods = $rollRates->filter(fn (CkpnRollRate $row): bool => (string) $row->segment_key === $segmentKey)
                ->pluck('periode_asal')
                ->unique()
                ->sort()
                ->values();
            $transitions = $rollRates->filter(fn (CkpnRollRate $row): bool => (string) $row->segment_key === $segmentKey)
                ->groupBy(fn (CkpnRollRate $row): string => $row->periode_asal.'|'.$row->bucket_asal);

            // Inisialisasi state vector Markov: debitur dimulai 100% di bucket asal.
            $state = [$bucketAsal => 1.0];
            $pdOneMonth = 0.0;

            // Iterasi maju melalui setiap periode transisi (t-1 → t) sesuai urutan kronologis.
            foreach ($periods as $periodIndex => $period) {
                $nextState = [];
                foreach ($state as $bucket => $weight) {
                    // Jika bucket sudah masuk loss state (WO/default), biarkan probabilitas terakumulasi.
                    if (in_array($bucket, $lossBuckets, true)) {
                        $nextState[$bucket] = ($nextState[$bucket] ?? 0.0) + $weight;

                        continue;
                    }

                    // Ambil baris transisi untuk kombinasi periode+bucket_asal ini.
                    $bucketRows = $transitions->get($period.'|'.$bucket, collect());
                    $total = $bucketRows->sum('jumlah_rekening');
                    if ($total <= 0) {
                        // Tidak ada data transisi: asumsi absorbing state (tetap di bucket yang sama).
                        $nextState[$bucket] = ($nextState[$bucket] ?? 0.0) + $weight;

                        continue;
                    }

                    // Hitung probabilitas transisi ke setiap bucket tujuan, lalu terapkan ke state vector.
                    foreach ($bucketRows as $row) {
                        $destination = (string) $row->bucket_tujuan;
                        // P(bucket_asal → bucket_tujuan) = jumlah_rekening / total_rekening_di_bucket_asal
                        $probability = ((int) $row->jumlah_rekening) / $total;
                        // Distribusikan bobot probabilitas ke state tujuan
                        $nextState[$destination] = ($nextState[$destination] ?? 0.0) + ($weight * $probability);
                    }
                }

                // Perbarui state vector untuk iterasi berikutnya.
                $state = $nextState;

                // Hitung probabilitas kumulatif ke loss state setelah satu periode (PD 1 bulan).
                $lossProbability = array_sum(array_intersect_key($state, array_flip($lossBuckets)));
                if ($periodIndex === 0) {
                    $pdOneMonth = $lossProbability;
                }
            }

            // PD kumulatif = total massa probabilitas yang telah mencapai loss state setelah N periode.
            $pdCumulative = array_sum(array_intersect_key($state, array_flip($lossBuckets)));
            $result[] = [
                'segment_key' => $segmentKey,
                'bucket_asal' => $bucketAsal,
                'pd_1_bulan' => round(min(1.0, max(0.0, $pdOneMonth)), 6),
                'pd_kumulatif' => round(min(1.0, max(0.0, $pdCumulative)), 6),
                'netflow_to_loss' => round(min(1.0, max(0.0, $pdCumulative)), 6),
            ];
        }

        return $result;
    }
}
