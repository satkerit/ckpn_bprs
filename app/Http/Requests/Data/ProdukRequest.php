<?php

namespace App\Http\Requests\Data;

use Illuminate\Validation\Rule;

/**
 * Validasi input manual data produk pembiayaan.
 */
class ProdukRequest extends DataRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'kdprd' => ['required', 'string', 'max:10'],
            'nama' => ['required', 'string', 'max:100'],
            'pokpby' => ['required', 'string', 'max:10', 'exists:kode_akad,pokpby'],
            'aktif' => ['nullable', 'boolean'],
        ];

        // Input manual bersifat upsert saat menambah, sehingga rule unik hanya
        // diuji pada update.
        if ($this->isUpdating()) {
            $rules['kdprd'][] = Rule::unique('produk_pembiayaan', 'kdprd')
                ->ignore($this->route('id'), 'kdprd');
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = parent::validatedData();

        // Form manual selalu mengirim kolom ini (hidden input + checkbox),
        // sedangkan pada file impor kolom "aktif" opsional: sel kosong berarti
        // status aktif dibiarkan seperti data yang sudah tersimpan.
        if ($this->isUpdating() && ! array_key_exists('aktif', $data)) {
            $data['aktif'] = false;
        }

        if (array_key_exists('aktif', $data)) {
            if ($data['aktif'] === null) {
                unset($data['aktif']);
            } else {
                $data['aktif'] = $this->boolean('aktif');
            }
        }

        return $data;
    }
}
