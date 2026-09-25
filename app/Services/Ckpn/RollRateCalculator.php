<?php

namespace App\Services\Ckpn;

use App\Models\CkpnRollRate;
use App\Models\HistoryPembiayaan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RollRateCalculator
{
    public function __construct(private readonly AkadRoleResolver $roles) {}

    public function build(int $runId, string $periode, string $previousPeriode, int $lookbackBulan): void
    {
        $target = now()->setDate((int) substr($periode, 0, 4), (int) substr($periode, 4, 2), 1);
        $periods = collect(range(1, max(1, $lookbackBulan)))
            ->map(fn (int $month): string => $target->copy()->subMonths($month)->format('Ym'))
            ->push($periode);
        
        $rows = collect();

        // P2 FIX: Query per pasangan periode (T-1 → T) instead of loading entire lookback window.
        // This reduces memory footprint significantly for large portfolios.
        foreach ($periods->reject(fn (string $period): bool => $period === $periode) as $period) {
            $next = now()->setDate((int) substr($period, 0, 4), (int) substr($period, 4, 2), 1)->addMonth()->format('Ym');
            if ($periods->contains($next)) {
                // P2 FIX: Load only the two periods needed for this transition (T-1 and T)
                $histories = HistoryPembiayaan::query()
                    ->with('pembiayaan')
                    ->whereIn('periode', [$period, $next])
                    ->get();
                $rows = $rows->merge($this->calculate($histories, $next, $period, $runId));
            }
        }

        DB::transaction(function () use ($rows, $runId): void {
            CkpnRollRate::query()->where('ckpn_run_id', $runId)->delete();
            if ($rows->isNotEmpty()) {
                CkpnRollRate::query()->insert($rows->all());
            }
        });
    }

    /**
     * @param  Collection<int, HistoryPembiayaan>  $histories
     * @return Collection<int, array<string, mixed>>
     */
    public function calculate(Collection $histories, string $periode, string $previousPeriode, int $runId): Collection
    {
        $current = $histories->where('periode', $periode)->keyBy('nokontrak');
        $previous = $histories->where('periode', $previousPeriode)->keyBy('nokontrak');
        $rows = collect();

        foreach ($previous as $nokontrak => $before) {
            // Snapshot bersama seluruh metode PD (Netflow dan Migration).
            // Rekening di luar populasi periode asal tidak ikut menyusun matriks.
            if (! $this->roles->masukPerhitungan($before->pokpby, $before)) {
                continue;
            }

            $after = $current->get($nokontrak);
            $bucketBefore = app(BucketClassifier::class)->classify($before->haritgk, $before->stsacc, $before->isWriteoff());
            $bucketAfter = $after === null
                ? 'PO'
                : app(BucketClassifier::class)->classify($after->haritgk, $after->stsacc, $after->isWriteoff());

            $rows->push([
                'ckpn_run_id' => $runId,
                'periode_asal' => $previousPeriode,
                'periode_tujuan' => $periode,
                'segment_key' => $this->segment($before),
                'bucket_asal' => $bucketBefore,
                'bucket_tujuan' => $bucketAfter,
                'jumlah_rekening' => 1,
                'total_saldo' => $before->bakiDebet(),
            ]);
        }

        return $rows->groupBy(fn (array $row): string => json_encode([
            $row['segment_key'],
            $row['bucket_asal'],
            $row['bucket_tujuan'],
        ], JSON_THROW_ON_ERROR))->map(fn (Collection $group): array => [
            ...$group->first(),
            'jumlah_rekening' => $group->sum('jumlah_rekening'),
            'total_saldo' => $group->sum('total_saldo'),
        ])->values();
    }

    private function segment(HistoryPembiayaan $history): string
    {
        return implode('|', [
            'kdloc='.$history->kdloc,
            'pokpby='.$history->pokpby,
            'gunadeb='.$history->pembiayaan?->gunadeb,
            'kdprd='.$history->kdprd,
        ]);
    }
}
