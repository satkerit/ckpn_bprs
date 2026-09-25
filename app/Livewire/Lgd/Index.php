<?php

namespace App\Livewire\Lgd;

use App\Enums\MetodeLgd;
use App\Models\CkpnLgdHasil;
use App\Models\CkpnLgdParameter;
use App\Models\CkpnRun;
use App\Models\Pembiayaan;
use App\Services\Ckpn\LgdCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $periode = '';

    public string $segmentKey = '';

    public string $jnsjamin = '';

    public float $haircutPersen = 0;

    public float $biayaLelangPersen = 0;

    public string $metode = 'shortfall';

    public bool $isProcessing = false;

    public function simpanParameter(): void
    {
        Gate::authorize('lgd.input');

        $this->validate([
            'periode' => ['required', 'date_format:Ym'],
            'segmentKey' => ['nullable', 'string', 'max:255'],
            'jnsjamin' => ['nullable', 'string', 'max:10'],
            'haircutPersen' => ['required', 'numeric', 'between:0,100'],
            'biayaLelangPersen' => ['required', 'numeric', 'between:0,100'],
            'metode' => ['required', 'in:shortfall,expected_recovery'],
        ]);

        CkpnLgdParameter::query()->updateOrCreate(
            [
                'periode' => $this->periode,
                'metode' => $this->metode,
                'jnsjamin' => $this->jnsjamin ?: null,
                'segment_key' => $this->segmentKey ?: null,
            ],
            [
                'haircut_persen' => $this->haircutPersen,
                'biaya_lelang_persen' => $this->biayaLelangPersen,
            ],
        );

        session()->flash('status', 'Parameter LGD berhasil disimpan.');
    }

    public function hitung(): void
    {
        Gate::authorize('lgd.calculate');

        $this->isProcessing = true;

        try {
            $this->validate(['periode' => ['required', 'date_format:Ym']]);
            $run = CkpnRun::query()->where('periode', $this->periode)->latest()->firstOrFail();
            $hasil = app(LgdCalculator::class)->calculate(
                Pembiayaan::query()->with([
                    'agunan',
                    'histories' => fn ($q) => $q->where('periode', $this->periode),
                ])->get(),
                $this->periode,
                $this->segmentKey,
                $this->jnsjamin ?: null,
                MetodeLgd::from($this->metode),
            );

            CkpnLgdHasil::query()->updateOrCreate(
                ['ckpn_run_id' => $run->id, 'metode' => $this->metode, 'segment_key' => $this->segmentKey, 'jnsjamin' => $this->jnsjamin ?: null],
                $hasil,
            );
            session()->flash('status', 'LGD berhasil dihitung.');
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render(): View
    {
        Gate::authorize('lgd.view');

        return view('livewire.lgd.index', [
            'hasil' => CkpnLgdHasil::query()->latest()->limit(20)->get(),
        ]);
    }
}
