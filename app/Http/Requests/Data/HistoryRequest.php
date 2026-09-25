<?php

namespace App\Http\Requests\Data;

use Illuminate\Validation\Rule;

/**
 * Validasi input manual data history pembiayaan.
 */
class HistoryRequest extends DataRequest
{
    protected function dateColumns(): array
    {
        return ['tglexp'];
    }

    protected function blankDefaults(): array
    {
        return [
            'osmgnc' => 0,
            'tgkmdl' => 0,
            'tgkmgn' => 0,
            'haritgk' => 0,
            'col' => 1,
            'stsrec' => 'A',
            'ppka' => 0,
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            // Nomor kontrak tidak wajib terdaftar pada master pembiayaan:
            // keterbatasan data master membuat nomor kontrak pada data history
            // sering belum ada padanannya. Relasi ke master tidak dipaksakan.
            'nokontrak' => ['required', 'string', 'max:30'],
            // Periode tidak dibatasi ke tabel ckpn_periode: konteks penyimpanan
            // data (input manual maupun upload) berbeda dengan penatapan periode
            // perhitungan CKPN. Cek terkunci tetap di assertPeriodeNotLocked().
            'periode' => ['required', 'digits:6'],
            'kdprd' => ['required', 'string', 'max:10', 'exists:produk_pembiayaan,kdprd'],
            'kdloc' => ['required', 'string', 'max:5', 'exists:kantor,kdloc'],
            'pokpby' => ['required', 'string', 'max:10', 'exists:kode_akad,pokpby'],
            'tglexp' => ['nullable', 'date'],
            'osmdlc' => ['required', 'numeric', 'between:0,9999999999999999.99'],
            'osmgnc' => ['nullable', 'numeric', 'between:0,9999999999999999.99'],
            'tgkmdl' => ['nullable', 'numeric', 'between:0,9999999999999999.99'],
            'tgkmgn' => ['nullable', 'numeric', 'between:0,9999999999999999.99'],
            'haritgk' => ['nullable', 'integer', 'between:0,2147483647'],
            'col' => ['nullable', 'integer', 'between:1,5'],
            'stsrec' => ['nullable', 'string', 'max:1'],
            'stsacc' => ['nullable', 'string', 'max:2'],
            'ppka' => ['nullable', 'numeric', 'between:0,9999999999999999.99'],
        ];

        // Input manual bersifat upsert saat menambah, sehingga rule unik hanya
        // diuji pada update. Kunci unik tabel ini adalah (nokontrak, periode).
        if ($this->isUpdating()) {
            $rules['nokontrak'][] = Rule::unique('history_pembiayaan', 'nokontrak')
                ->where('periode', $this->input('periode'))
                ->ignore($this->route('id'));
        }

        return $rules;
    }
}
