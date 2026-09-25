<?php

namespace App\Enums;

/**
 * Dimensi yang dapat dipakai untuk segmentasi CKPN.
 *
 * Segmentasi bersifat dinamis: user menyusun urutan level dari daftar ini.
 */
enum DimensiSegmentasi: string
{
    case Lokasi = 'kdloc';
    case Akad = 'pokpby';
    case Penggunaan = 'gunadeb';
    case Produk = 'kdprd';

    public function label(): string
    {
        return match ($this) {
            self::Lokasi => 'Lokasi Kantor',
            self::Akad => 'Kode Akad',
            self::Penggunaan => 'Jenis Penggunaan',
            self::Produk => 'Kode Produk',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
