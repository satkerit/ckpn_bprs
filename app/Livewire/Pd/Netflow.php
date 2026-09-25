<?php

namespace App\Livewire\Pd;

use App\Enums\DimensiSegmentasi;
use App\Models\CkpnPdNetflow;
use App\Models\CkpnPeriode;
use App\Models\CkpnRollRate;
use App\Models\CkpnRun;
use App\Services\Ckpn\CkpnCalculator;
use App\Services\Ckpn\PdNetflowCalculator;
use App\Services\Ckpn\RollRateCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Netflow extends Component
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
            'metode' => 'netflow',
            'metode_lgd' => 'shortfall',
            'status' => 'running',
            'started_at' => now(),
            'created_by' => auth()->id(),
        ]);

        try {
            $previousPeriode = now()->setDate((int) substr($this->periode, 0, 4), (int) substr($this->periode, 4, 2), 1)
                ->subMonth()
                ->format('Ym');

            // Kunci segmentasi harus sama persis dengan yang dipakai CkpnCalculator,
            // jika tidak PD tidak akan ditemukan saat pairing hasil.
            $dimensions = $run->segments()->orderBy('urutan')->pluck('dimensi')->all()
                ?: array_column(DimensiSegmentasi::cases(), 'value');

            app(RollRateCalculator::class)->build(
                $run->id,
                $this->periode,
                $previousPeriode,
                $this->lookbackBulan,
                $dimensions,
            );

            $pdRows = app(PdNetflowCalculator::class)->calculate(
                CkpnRollRate::query()->where('ckpn_run_id', $run->id)->get(),
            );

            foreach ($pdRows as $pd) {
                CkpnPdNetflow::query()->create([
                    'ckpn_run_id' => $run->id,
                    ...$pd,
                ]);
            }

            app(CkpnCalculator::class)->calculateRun($run);
            $run->update(['status' => 'done', 'finished_at' => now()]);
            session()->flash('status', "PD Netflow selesai. Run #{$run->id}.");
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'log' => $exception->getMessage(),
            ]);
            throw $exception;
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render(): View
    {
        Gate::authorize('pd.view');

        return view('livewire.pd.netflow', [
            'runs' => CkpnRun::query()->where('metode', 'netflow')->latest()->limit(10)->get(),
        ]);
    }
}
