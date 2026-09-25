<?php

namespace App\Livewire\Setup;

use App\Enums\SyaratMasukCkpn;
use App\Models\AkadRole;
use App\Models\KodeAkad;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CkpnRole extends Component
{
    public string $pokpby = '';

    public bool $ead_osmdlc = true;

    public bool $ead_osmgnc = false;

    public bool $ead_tgkmdl = false;

    public bool $ead_tgkmgn = false;

    public string $syarat_masuk = 'selalu';

    public function mount(): void
    {
        $this->syarat_masuk = SyaratMasukCkpn::Selalu->value;
    }

    public function simpan(): void
    {
        Gate::authorize('setup.manage');

        $data = $this->validate([
            'pokpby' => ['required', 'string', 'max:10', Rule::exists('kode_akad', 'pokpby')],
            'ead_osmdlc' => ['boolean'],
            'ead_osmgnc' => ['boolean'],
            'ead_tgkmdl' => ['boolean'],
            'ead_tgkmgn' => ['boolean'],
            'syarat_masuk' => ['required', Rule::in(array_keys(SyaratMasukCkpn::options()))],
        ], attributes: [
            'pokpby' => 'Kode Akad',
            'syarat_masuk' => 'Syarat Masuk CKPN',
        ]);

        AkadRole::query()->updateOrCreate(
            ['pokpby' => $data['pokpby']],
            [
                'ead_osmdlc' => $data['ead_osmdlc'],
                'ead_osmgnc' => $data['ead_osmgnc'],
                'ead_tgkmdl' => $data['ead_tgkmdl'],
                'ead_tgkmgn' => $data['ead_tgkmgn'],
                'syarat_masuk' => $data['syarat_masuk'],
            ],
        );

        $this->reset(['pokpby', 'ead_osmdlc', 'ead_osmgnc', 'ead_tgkmdl', 'ead_tgkmgn']);
        $this->ead_osmdlc = true;
        $this->syarat_masuk = SyaratMasukCkpn::Selalu->value;
        $this->resetValidation();

        session()->flash('status', 'Role CKPN untuk akad tersebut disimpan.');
    }

    public function hapus(string $pokpby): void
    {
        Gate::authorize('setup.manage');

        AkadRole::query()->where('pokpby', $pokpby)->delete();

        session()->flash('status', "Role CKPN akad {$pokpby} dikembalikan ke default.");
    }

    public function render(): View
    {
        Gate::authorize('setup.manage');

        return view('livewire.setup.ckpn-role', [
            'roles' => AkadRole::query()->with('akad')->orderBy('pokpby')->get(),
            'akads' => KodeAkad::query()->orderBy('pokpby')->get(),
            'syaratOptions' => SyaratMasukCkpn::options(),
        ]);
    }
}
