<?php

namespace App\Http\Requests\Data;

use Illuminate\Validation\Rule;

/**
 * Validasi input manual data kantor.
 */
class KantorRequest extends DataRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'kdloc' => ['required', 'string', 'max:5'],
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ];

        // Input manual bersifat upsert saat menambah, sehingga rule unik hanya
        // diuji pada update.
        if ($this->isUpdating()) {
            $rules['kdloc'][] = Rule::unique('kantor', 'kdloc')
                ->ignore($this->route('id'), 'kdloc');
        }

        return $rules;
    }
}
