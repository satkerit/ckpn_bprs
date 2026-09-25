<?php

namespace App\Livewire\Master;

use App\Models\AuditLog;
use App\Models\KodeAkad as KodeAkadModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Master kode akad: kode yang dipakai bersama oleh produk pembiayaan,
 * pembiayaan, dan history pembiayaan.
 */
#[Layout('layouts.app')]
class KodeAkad extends Component
{
    /** Pilihan skema perhitungan beserta labelnya. */
    public const SKEMA = [
        'margin' => 'Margin',
        'ujrah' => 'Ujrah',
        'bagihasil' => 'Bagi Hasil',
        'sewa' => 'Sewa',
    ];

    /** Kode akad yang sedang diedit; null berarti mode tambah. */
    public ?string $editing = null;

    public string $pokpby = '';

    public string $nama = '';

    public string $skema = 'margin';

    public bool $aktif = true;

    public function simpan(): void
    {
        Gate::authorize('setup.manage');

        $data = $this->validate([
            'pokpby' => [
                'required', 'string', 'max:10',
                Rule::unique('kode_akad', 'pokpby')->ignore($this->editing, 'pokpby'),
            ],
            'nama' => ['required', 'string', 'max:100'],
            'skema' => ['required', Rule::in(array_keys(self::SKEMA))],
            'aktif' => ['boolean'],
        ], attributes: [
            'pokpby' => 'Kode Akad',
            'nama' => 'Nama Akad',
            'skema' => 'Skema',
            'aktif' => 'Status Aktif',
        ]);

        if ($this->editing === null) {
            $akad = KodeAkadModel::query()->create($data);
            AuditLog::catat('kode_akad.created', $akad, ['pokpby' => $akad->pokpby]);
            $pesan = "Kode akad {$akad->pokpby} ditambahkan.";
        } else {
            $akad = KodeAkadModel::query()->findOrFail($this->editing);
            $akad->update($data);
            AuditLog::catat('kode_akad.updated', $akad, ['pokpby' => $akad->pokpby]);
            $pesan = "Kode akad {$akad->pokpby} diperbarui.";
        }

        $this->batal();
        session()->flash('status', $pesan);
    }

    public function edit(string $pokpby): void
    {
        Gate::authorize('setup.manage');

        $akad = KodeAkadModel::query()->findOrFail($pokpby);

        $this->resetValidation();
        $this->editing = $akad->pokpby;
        $this->pokpby = $akad->pokpby;
        $this->nama = $akad->nama;
        $this->skema = $akad->skema;
        $this->aktif = (bool) $akad->aktif;
    }

    public function batal(): void
    {
        $this->reset(['editing', 'pokpby', 'nama', 'skema']);
        $this->aktif = true;
        $this->resetValidation();
    }

    public function hapus(string $pokpby): void
    {
        Gate::authorize('setup.manage');

        $akad = KodeAkadModel::query()->findOrFail($pokpby);

        // Akad yang masih dipakai tidak boleh dihapus agar data produk,
        // pembiayaan, dan history tidak kehilangan referensinya.
        if ($akad->jumlahPemakai() > 0) {
            $this->addError(
                'hapus',
                "Kode akad {$akad->pokpby} masih dipakai data produk/pembiayaan/history, jadi tidak dapat dihapus. Nonaktifkan saja bila sudah tidak dipakai.",
            );

            return;
        }

        AuditLog::catat('kode_akad.deleted', $akad, ['pokpby' => $akad->pokpby]);
        $akad->delete();

        if ($this->editing === $pokpby) {
            $this->batal();
        }

        session()->flash('status', "Kode akad {$pokpby} dihapus.");
    }

    public function render(): View
    {
        Gate::authorize('setup.manage');

        return view('livewire.master.kode-akad', [
            'akads' => KodeAkadModel::query()
                ->withCount(['produk', 'pembiayaan', 'history'])
                ->orderBy('pokpby')
                ->get(),
            'skemaOptions' => self::SKEMA,
        ]);
    }
}
