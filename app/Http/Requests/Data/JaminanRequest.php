<?php

namespace App\Http\Requests\Data;

use Illuminate\Validation\Rule;

/**
 * Validasi input manual data jaminan (agunan).
 */
class JaminanRequest extends DataRequest
{
    protected function dateColumns(): array
    {
        return ['tgltaks'];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'nokontrak' => ['required', 'string', 'max:30', 'exists:pembiayaan,nokontrak'],
            'noreg' => ['required', 'string', 'max:30'],
            'urut' => ['required', 'integer', 'between:1,2147483647'],
            'tgltaks' => ['nullable', 'date'],
            'jnsjamin' => ['required', 'string', 'max:20', 'exists:setup_jaminan,kdjam'],
            'nominallikuid' => ['required', 'numeric', 'between:0,9999999999999999.99'],
        ];

        // Input manual bersifat upsert saat menambah, sehingga rule unik hanya
        // diuji pada update. Kunci unik tabel ini adalah (nokontrak, noreg, urut).
        if ($this->isUpdating()) {
            $rules['nokontrak'][] = Rule::unique('agunan', 'nokontrak')
                ->where('noreg', $this->input('noreg'))
                ->where('urut', $this->input('urut'))
                ->ignore($this->route('id'));
        }

        return $rules;
    }
}
