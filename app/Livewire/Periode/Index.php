<?php

namespace App\Livewire\Periode;

use App\Enums\StatusPeriode;
use App\Models\CkpnPeriode;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $periode = '';

    public string $keterangan = '';

    public function simpan(): void
    {
        Gate::authorize('periode.manage');

        $this->validate([
            'periode' => ['required', 'date_format:Ym', 'unique:ckpn_periode,periode'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        CkpnPeriode::query()->create([
            'periode' => $this->periode,
            'status' => StatusPeriode::Draft,
            'keterangan' => $this->keterangan ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->reset(['periode', 'keterangan']);
        session()->flash('status', 'Periode berhasil dibuat.');
    }

    public function kunci(int $id): void
    {
        Gate::authorize('periode.manage');

        $periode = CkpnPeriode::query()->findOrFail($id);
        abort_if($periode->isLocked(), 422, 'Periode sudah terkunci.');
        $periode->update(['status' => StatusPeriode::Locked, 'locked_at' => now()]);
    }

    public function buka(int $id): void
    {
        Gate::authorize('periode.unlock');

        $periode = CkpnPeriode::query()->findOrFail($id);
        abort_if($periode->status === StatusPeriode::Final, 422, 'Periode final tidak dapat dibuka.');
        $periode->update(['status' => StatusPeriode::Draft, 'locked_at' => null]);
    }

    public function render(): View
    {
        Gate::authorize('periode.view');

        return view('livewire.periode.index', [
            'periodes' => CkpnPeriode::query()->latest('periode')->get(),
        ]);
    }
}
