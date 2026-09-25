<?php

namespace App\Http\Requests\Data;

use App\Enums\JenisUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Dasar validasi input manual data per jenis.
 *
 * Otorisasi tetap ditangani Gate di controller; kelas ini memvalidasi input,
 * menyesuaikannya dengan tipe kolom di database, dan dipakai ulang untuk
 * memvalidasi setiap baris file unggahan.
 */
abstract class DataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kelas request yang sesuai untuk jenis data tertentu.
     *
     * @return class-string<DataRequest>
     */
    public static function for(JenisUpload $jenis): string
    {
        return match ($jenis) {
            JenisUpload::Pembiayaan => PembiayaanRequest::class,
            JenisUpload::History => HistoryRequest::class,
            JenisUpload::Jaminan => JaminanRequest::class,
            JenisUpload::Kantor => KantorRequest::class,
            JenisUpload::Produk => ProdukRequest::class,
            JenisUpload::JaminanSetup => JaminanSetupRequest::class,
        };
    }

    /**
     * Validasi satu baris data hasil impor memakai rule input manual, sehingga
     * kesalahan dilaporkan sebagai pesan validasi, bukan error database.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, array<string|int, mixed>>|null  $lookupCache
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function validateRow(JenisUpload $jenis, array $row, ?array $lookupCache = null): array
    {
        $class = static::for($jenis);
        /** @var DataRequest $request */
        $request = new $class;
        $request->replace($row);

        $rules = $request->rules();
        $messages = $request->messages();

        if ($lookupCache !== null) {
            $rules = static::replaceExistsWithInRules($rules, $lookupCache);
            $messages['in'] = 'Nilai :attribute tidak ditemukan pada data referensi.';
        }

        $validator = Validator::make($row, $rules, $messages, $request->attributes());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $request->setValidator($validator)->validatedData();
    }

    /**
     * Ganti rule exists dengan rule in-memory agar import ratusan ribu baris
     * tidak menjalankan query database per baris untuk setiap kolom foreign key.
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, array<string|int, mixed>>  $lookupCache
     * @return array<string, mixed>
     */
    protected static function replaceExistsWithInRules(array $rules, array $lookupCache): array
    {
        $map = [
            'produk_pembiayaan,kdprd' => 'kdprd',
            'kantor,kdloc' => 'kdloc',
            'kode_akad,pokpby' => 'pokpby',
            'jenis_penggunaan,kode' => 'gunadeb',
            'setup_jaminan,kdjam' => 'kdjam',
            'pembiayaan,nokontrak' => 'nokontrak',
        ];

        foreach ($rules as $field => $fieldRules) {
            if (! is_array($fieldRules)) {
                continue;
            }

            foreach ($fieldRules as $idx => $rule) {
                if (is_string($rule) && str_starts_with($rule, 'exists:')) {
                    $target = substr($rule, 7);
                    $cacheKey = $map[$target] ?? null;

                    if ($cacheKey && isset($lookupCache[$cacheKey])) {
                        $validValues = array_keys($lookupCache[$cacheKey]);
                        $rules[$field][$idx] = Rule::in($validValues);
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Kolom tanggal yang tersimpan sebagai char(8) dengan format Ymd.
     *
     * @return array<int, string>
     */
    protected function dateColumns(): array
    {
        return [];
    }

    /**
     * Kolom NOT NULL yang memakai nilai default bila form dikirim kosong,
     * supaya input kosong tidak tersimpan sebagai NULL.
     *
     * @return array<string, mixed>
     */
    protected function blankDefaults(): array
    {
        return [];
    }

    /**
     * Data tervalidasi siap simpan: tanggal sudah format Ymd dan kolom kosong
     * pada kolom NOT NULL sudah memakai nilai default.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        foreach ($this->dateColumns() as $column) {
            if (! array_key_exists($column, $data)) {
                continue;
            }

            $data[$column] = filled($data[$column])
                ? Carbon::parse($data[$column])->format('Ymd')
                : null;
        }

        foreach ($this->blankDefaults() as $column => $default) {
            if (array_key_exists($column, $data) && ($data[$column] === null || $data[$column] === '')) {
                $data[$column] = $default;
            }
        }

        return $data;
    }

    /**
     * Nama kolom pada pesan validasi, dipakai input manual maupun impor.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nokontrak' => 'No Kontrak',
            'nocif' => 'No CIF',
            'nama' => 'Nama',
            'kdprd' => 'Kode Produk',
            'kdloc' => 'Kode Kantor',
            'pokpby' => 'Kode Akad',
            'gunadeb' => 'Guna Debitur',
            'tglwo' => 'Tgl Write-off',
            'periode' => 'Periode',
            'tglexp' => 'Tgl Jatuh Tempo',
            'osmdlc' => 'OS Pokok',
            'osmgnc' => 'OS Margin',
            'tgkmdl' => 'Tunggakan Pokok',
            'tgkmgn' => 'Tunggakan Margin',
            'haritgk' => 'Hari Tunggakan',
            'col' => 'Kolektibilitas',
            'stsrec' => 'Status Recovery',
            'stsacc' => 'Status Account',
            'ppka' => 'PPKA',
            'noreg' => 'No Registrasi',
            'urut' => 'Urut',
            'tgltaks' => 'Tgl Taksasi',
            'jnsjamin' => 'Jenis Jaminan',
            'nominallikuid' => 'Nominal Likuidasi',
            'alamat' => 'Alamat',
            'aktif' => 'Status Aktif',
            'kdjam' => 'Kode Jaminan',
            'ket' => 'Keterangan Jenis Jaminan',
            'bobot' => 'Bobot Pengurang',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'integer' => 'Kolom :attribute harus berupa angka bulat.',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'between' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
            'date' => 'Kolom :attribute harus berupa tanggal yang valid.',
            'digits' => 'Kolom :attribute harus terdiri dari :digits angka.',
            'boolean' => 'Kolom :attribute harus bernilai ya atau tidak.',
            'exists' => 'Nilai :attribute tidak ditemukan pada data referensi.',
            'unique' => 'Nilai :attribute sudah dipakai data lain.',
        ];
    }

    /**
     * True saat dipakai oleh route update, yang selalu memiliki parameter id.
     */
    protected function isUpdating(): bool
    {
        return $this->route('id') !== null;
    }
}
