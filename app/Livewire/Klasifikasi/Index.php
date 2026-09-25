<?php

namespace App\Livewire\Klasifikasi;

use App\Enums\TipeCkpn;
use App\Models\AuditLog;
use App\Models\CkpnKlasifikasi;
use App\Models\CkpnPeriode;
use App\Models\SetupParameter;
use App\Services\Ckpn\KlasifikasiCkpnService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $periode = '';

    public string $cari = '';

    public int $maksRekening = 10;

    public function mount(): void
    {
        $this->maksRekening = (int) SetupParameter::get('ckpn.individual_maks_rekening', 10);
        $this->periode = (string) CkpnPeriode::query()->latest('periode')->value('periode');
    }

    public function simpanParameter(): void
    {
        Gate::authorize('klasifikasi.manage');

        $data = $this->validate([
            'maksRekening' => ['required', 'integer', 'min:1', 'max:10000'],
        ], attributes: ['maksRekening' => 'Jumlah rekening individual']);

        SetupParameter::set(
            'ckpn.individual_maks_rekening',
            (int) $data['maksRekening'],
            'ckpn',
            'Jumlah maksimal rekening NPF outstanding terbesar yang diklasifikasikan CKPN Individual',
        );

        session()->flash('status', 'Parameter jumlah rekening individual disimpan.');
    }

    public function klasifikasikan(): void
    {
        Gate::authorize('klasifikasi.manage');

        $data = $this->validate(['periode' => ['required', 'date_format:Ym']]);

        $hasil = app(KlasifikasiCkpnService::class)->klasifikasikan($data['periode']);

        AuditLog::catat('klasifikasi.recalculate', null, [
            'periode' => $data['periode'],
            'individual' => $hasil['individual'],
            'kolektif' => $hasil['kolektif'],
        ]);

        $this->resetPage();

        session()->flash(
            'status',
            "Klasifikasi {$data['periode']}: {$hasil['individual']} individual, {$hasil['kolektif']} kolektif.",
        );
    }

    public function setIndividual(string $nokontrak, bool $individual, ?string $nilai = null): void
    {
        Gate::authorize('klasifikasi.manage');

        $tipe = $individual ? TipeCkpn::Individual : TipeCkpn::Kolektif;

        app(KlasifikasiCkpnService::class)->override(
            $this->periode,
            $nokontrak,
            $tipe,
            $individual ? max(0.0, (float) $nilai) : null,
        );

        AuditLog::catat('klasifikasi.override', null, [
            'periode' => $this->periode,
            'nokontrak' => $nokontrak,
            'tipe' => $tipe->value,
        ]);

        session()->flash('status', "Klasifikasi manual {$nokontrak} disimpan.");
    }

    public function render(): View
    {
        Gate::authorize('klasifikasi.view');

        $klasifikasi = CkpnKlasifikasi::query()
            ->where('periode', $this->periode)
            ->when($this->cari !== '', fn ($q) => $q->where('nokontrak', 'like', "%{$this->cari}%"))
            ->orderByDesc('nilai_individual')
            ->orderBy('nokontrak')
            ->paginate(25)
            ->withQueryString();

        return view('livewire.klasifikasi.index', [
            'periodes' => CkpnPeriode::query()->latest('periode')->get(),
            'klasifikasi' => $klasifikasi,
            'ringkasan' => CkpnKlasifikasi::query()
                ->where('periode', $this->periode)
                ->selectRaw('tipe, COUNT(*) as jumlah')
                ->groupBy('tipe')
                ->pluck('jumlah', 'tipe'),
        ]);
    }
}
