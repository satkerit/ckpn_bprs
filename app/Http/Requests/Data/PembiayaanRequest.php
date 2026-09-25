<?php

namespace App\Http\Requests\Data;

use Illuminate\Validation\Rule;

/**
 * Validasi input manual data pembiayaan.
 */
class PembiayaanRequest extends DataRequest
{
    protected function dateColumns(): array
    {
        return ['tglwo'];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'nokontrak' => ['required', 'string', 'max:30'],
            'nocif' => ['required', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:100'],
            'kdprd' => ['required', 'string', 'max:10', 'exists:produk_pembiayaan,kdprd'],
            'kdloc' => ['required', 'string', 'max:5', 'exists:kantor,kdloc'],
            'pokpby' => ['required', 'string', 'max:10', 'exists:kode_akad,pokpby'],
            'gunadeb' => ['required', 'string', 'max:1', 'exists:jenis_penggunaan,kode'],
            'tglwo' => ['nullable', 'date'],
        ];

        // Input manual bersifat upsert saat menambah (kontrak yang sudah ada
        // akan diperbarui), sehingga rule unik hanya diuji pada update.
        if ($this->isUpdating()) {
            $rules['nokontrak'][] = Rule::unique('pembiayaan', 'nokontrak')
                ->ignore($this->route('id'), 'nokontrak');
        }

        return $rules;
    }
}
