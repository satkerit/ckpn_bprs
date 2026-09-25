<?php

namespace App\Services\Ckpn;

use App\Models\CkpnRollRate;
use Illuminate\Support\Collection;

class PdMigrationCalculator
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

        foreach ($rollRates->groupBy('segment_key') as $segmentKey => $segmentRows) {
            $periods = $segmentRows->pluck('periode_asal')->unique()->sort()->values();
            $transitions = $segmentRows->groupBy('periode_asal')->map(fn (Collection $rows): Collection => $this->probabilities($rows));
            $buckets = $segmentRows->pluck('bucket_asal')->map(fn ($bucket): string => (string) $bucket)->unique();

            foreach ($buckets as $bucket) {
                $state = [$bucket => 1.0];
                $oneMonth = [];

                foreach ($periods as $periodIndex => $period) {
                    $state = $this->step($state, $transitions->get($period, collect()), $lossBuckets);
                    if ($periodIndex === 0) {
                        $oneMonth = $state;
                    }
                }

                $cumulative = $this->lossProbability($state, $lossBuckets);
                $result[] = [
                    'segment_key' => (string) $segmentKey,
                    'bucket_asal' => $bucket,
                    'pd_1_bulan' => round($this->lossProbability($oneMonth, $lossBuckets), 6),
                    'pd_kumulatif' => round($cumulative, 6),
                    'netflow_to_loss' => round($cumulative, 6),
                ];
            }
        }

        return $result;
    }

    /** @return Collection<string, array<string, float>> */
    private function probabilities(Collection $rows): Collection
    {
        return $rows->groupBy('bucket_asal')->map(function (Collection $bucketRows): array {
            $total = $bucketRows->sum('jumlah_rekening');

            return $bucketRows->groupBy('bucket_tujuan')->mapWithKeys(fn (Collection $destinationRows, string $destination): array => [
                $destination => $total > 0 ? $destinationRows->sum('jumlah_rekening') / $total : 0.0,
            ])->all();
        });
    }

    /**
     * @param  array<string, float>  $state
     * @param  Collection<string, array<string, float>>  $transitions
     * @param  array<int, string>  $lossBuckets
     * @return array<string, float>
     */
    private function step(array $state, Collection $transitions, array $lossBuckets): array
    {
        $next = [];

        foreach ($state as $bucket => $weight) {
            if (in_array($bucket, $lossBuckets, true)) {
                $next[$bucket] = ($next[$bucket] ?? 0.0) + $weight;

                continue;
            }

            $probabilities = $transitions->get($bucket, []);
            if ($probabilities === []) {
                $next[$bucket] = ($next[$bucket] ?? 0.0) + $weight;

                continue;
            }

            foreach ($probabilities as $destination => $probability) {
                $next[$destination] = ($next[$destination] ?? 0.0) + ($weight * $probability);
            }
        }

        return $next;
    }

    /** @param array<string, float> $state */
    private function lossProbability(array $state, array $lossBuckets): float
    {
        return min(1.0, max(0.0, array_sum(array_intersect_key($state, array_flip($lossBuckets)))));
    }
}
