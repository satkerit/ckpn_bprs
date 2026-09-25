<?php

namespace App\Http\Requests\Data;

use Illuminate\Validation\Rule;

/**
 * Validasi input manual data setup jaminan (master jenis jaminan).
 */
class JaminanSetupRequest extends DataRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'kdjam' => ['required', 'string', 'max:20'],
            'ket' => ['required', 'string', 'max:150'],
            'bobot' => ['required', 'numeric', 'min:0', 'max:100'],
        ];

        // Input manual bersifat upsert saat menambah, rule unik hanya diuji pada update.
        if ($this->isUpdating()) {
            $rules['kdjam'][] = Rule::unique('setup_jaminan', 'kdjam')
                ->ignore($this->route('id'), 'kdjam');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'bobot.min' => 'Bobot minimal 0%.',
            'bobot.max' => 'Bobot maksimal 100%.',
        ];
    }
}
