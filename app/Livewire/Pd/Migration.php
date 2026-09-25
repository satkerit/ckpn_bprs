<?php

namespace App\Livewire\Pd;

use App\Models\CkpnPdNetflow;
use App\Models\CkpnPeriode;
use App\Models\CkpnRollRate;
use App\Models\CkpnRun;
use App\Services\Ckpn\CkpnCalculator;
use App\Services\Ckpn\PdMigrationCalculator;
use App\Services\Ckpn\RollRateCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Migration extends Component
{
    public string $periode = '';

    public int $lookbackBulan = 12;

    public bool $isProcessing = false;

    public function hitung(): void
    {
        Gate::authorize('pd.calculate');

        $this->isProcessing = true;

        $this->validate([
            'periode' => ['required', 'date_format:Ym'],
            'lookbackBulan' => ['required', 'integer', 'in:12,24,36,60'],
        ]);

        abort_if(CkpnPeriode::query()->where('periode', $this->periode)->first()?->isLocked(), 422, 'Periode sudah terkunci atau final.');

        $run = CkpnRun::query()->create([
            'periode' => $this->periode,
            'lookback_bulan' => $this->lookbackBulan,
            'metode' => 'migration',
            'metode_lgd' => 'shortfall',
            'status' => 'running',
            'started_at' => now(),
            'created_by' => auth()->id(),
        ]);

        try {
            $previousPeriode = now()->setDate((int) substr($this->periode, 0, 4), (int) substr($this->periode, 4, 2), 1)
                ->subMonth()
                ->format('Ym');
            app(RollRateCalculator::class)->build($run->id, $this->periode, $previousPeriode, $this->lookbackBulan);

            foreach (app(PdMigrationCalculator::class)->calculate(CkpnRollRate::query()->where('ckpn_run_id', $run->id)->get()) as $pd) {
                CkpnPdNetflow::query()->create(['ckpn_run_id' => $run->id, ...$pd]);
            }

            app(CkpnCalculator::class)->calculateRun($run);
            $run->update(['status' => 'done', 'finished_at' => now()]);
            session()->flash('status', "PD Migration selesai. Run #{$run->id}.");
        } catch (\Throwable $exception) {
            $run->update(['status' => 'failed', 'finished_at' => now(), 'log' => $exception->getMessage()]);
            throw $exception;
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render(): View
    {
        Gate::authorize('pd.view');

        return view('livewire.pd.migration', [
            'runs' => CkpnRun::query()->where('metode', 'migration')->latest()->limit(10)->get(),
        ]);
    }
}
