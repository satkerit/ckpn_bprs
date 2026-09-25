<?php

namespace Database\Seeders;

use App\Enums\SyaratMasukCkpn;
use App\Models\AkadRole;
use Illuminate\Database\Seeder;

/**
 * Role default CKPN per kode akad, disadur dari dokumen metodologi
 * (docs/catatan.md): Murabahah/Multijasa selalu masuk dengan komponen
 * pokok + tunggakan margin; Musyarakah/IMBT hanya bila sudah jatuh tempo.
 */
class AkadRoleSeeder extends Seeder
{
    private const DEFAULTS = [
        // Murabahah: sisa pokok + tunggakan margin jatuh tagih, semua bucket.
        '06' => [
            'ead' => ['osmdlc' => true, 'osmgnc' => false, 'tgkmdl' => false, 'tgkmgn' => true],
            'syarat_masuk' => SyaratMasukCkpn::Selalu,
        ],
        // Ijarah Multijasa: sisa pokok + tunggakan ujrah jatuh tagih, semua bucket.
        '13' => [
            'ead' => ['osmdlc' => true, 'osmgnc' => false, 'tgkmdl' => false, 'tgkmgn' => true],
            'syarat_masuk' => SyaratMasukCkpn::Selalu,
        ],
        // Musyarakah lancar (belum jatuh tempo) out of scope PSAK 414;
        // menunggak/jatuh tempo: sisa modal syirkah + tunggakan bagi hasil.
        '03' => [
            'ead' => ['osmdlc' => true, 'osmgnc' => false, 'tgkmdl' => false, 'tgkmgn' => true],
            'syarat_masuk' => SyaratMasukCkpn::JatuhTempo,
        ],
        // IMBT lancar out of scope; menunggak/jatuh tagih: tunggakan pokok.
        '10' => [
            'ead' => ['osmdlc' => false, 'osmgnc' => false, 'tgkmdl' => true, 'tgkmgn' => false],
            'syarat_masuk' => SyaratMasukCkpn::JatuhTempo,
        ],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $pokpby => $config) {
            AkadRole::query()->updateOrCreate(
                ['pokpby' => $pokpby],
                [
                    'ead_osmdlc' => $config['ead']['osmdlc'],
                    'ead_osmgnc' => $config['ead']['osmgnc'],
                    'ead_tgkmdl' => $config['ead']['tgkmdl'],
                    'ead_tgkmgn' => $config['ead']['tgkmgn'],
                    'syarat_masuk' => $config['syarat_masuk'],
                ],
            );
        }
    }
}
