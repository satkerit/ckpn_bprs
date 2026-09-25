<?php

namespace Database\Seeders;

use App\Models\SetupParameter;
use Illuminate\Database\Seeder;

class SetupParameterSeeder extends Seeder
{
    /** Nilai default parameter sistem (dapat diubah lewat menu Setup). */
    private const DEFAULTS = [
        'perusahaan.nama' => 'BPRS Contoh',
        'perusahaan.kode_bank' => '900',
        'perusahaan.alamat' => 'Jl. Contoh No. 1',
        'perusahaan.npwp' => '',
        'perusahaan.logo' => '',
        'ckpn.threshold_individual' => 500000000,
        'ckpn.individual_maks_rekening' => 10,
        'ckpn.lookback_default' => 12,
        'ckpn.basis_pd' => 'debitur',
        'ckpn.col_default' => 3,
        'ckpn.haritgk_default' => 90,
        'ppka.persen_col_1' => 1,
        'ppka.persen_col_2' => 5,
        'ppka.persen_col_3' => 15,
        'ppka.persen_col_4' => 50,
        'ppka.persen_col_5' => 100,
        'metodologi.psak' => 'PSAK 414 / SAK Entitas Privat',
        'metodologi.pojk' => 'POJK No. 24 Tahun 2024',
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $kunci => $nilai) {
            $group = explode('.', $kunci)[0] ?? 'umum';

            if (SetupParameter::query()->where('kunci', $kunci)->doesntExist()) {
                SetupParameter::set($kunci, $nilai, $group);
            }
        }
    }
}
