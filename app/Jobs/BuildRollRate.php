<?php

namespace App\Jobs;

use App\Models\CkpnRollRate;
use App\Models\HistoryPembiayaan;
use App\Services\Ckpn\RollRateCalculator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class BuildRollRate implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $runId,
        public readonly string $periode,
        public readonly string $previousPeriode,
        public readonly int $lookbackBulan = 12,
    ) {}

    public function handle(RollRateCalculator $calculator): void
    {
        $target = now()->setDate((int) substr($this->periode, 0, 4), (int) substr($this->periode, 4, 2), 1);
        $periods = collect(range(1, max(1, $this->lookbackBulan)))
            ->map(fn (int $month): string => $target->copy()->subMonths($month)->format('Ym'))
            ->push($this->periode);
        $histories = HistoryPembiayaan::query()
            ->with('pembiayaan')
            ->whereIn('periode', $periods)
            ->get();

        $rows = collect();
        foreach ($periods->reject(fn (string $period): bool => $period === $this->periode) as $period) {
            $next = now()->setDate((int) substr($period, 0, 4), (int) substr($period, 4, 2), 1)->addMonth()->format('Ym');
            if (! $periods->contains($next)) {
                continue;
            }
            $rows = $rows->merge($calculator->calculate($histories, $next, $period, $this->runId));
        }

        DB::transaction(function () use ($rows): void {
            CkpnRollRate::query()->where('ckpn_run_id', $this->runId)->delete();
            if ($rows->isNotEmpty()) {
                CkpnRollRate::query()->insert($rows->all());
            }
        });
    }
}
