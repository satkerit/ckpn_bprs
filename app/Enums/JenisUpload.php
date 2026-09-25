<?php

namespace App\Enums;

/**
 * Jenis data yang dapat diunggah ke sistem.
 */
enum JenisUpload: string
{
    case Pembiayaan = 'pembiayaan';
    case History = 'history';
    case Jaminan = 'jaminan';
    case Kantor = 'kantor';
    case Produk = 'produk';
    case JaminanSetup = 'jaminan_setup';

    public function label(): string
    {
        return match ($this) {
            self::Pembiayaan => 'Data Pembiayaan',
            self::History => 'Data History Pembiayaan',
            self::Jaminan => 'Data Jaminan',
            self::Kantor => 'Data Kantor',
            self::Produk => 'Data Produk Pembiayaan',
            self::JaminanSetup => 'Setup Jaminan',
        };
    }

    /**
     * Semua kolom template per jenis data, termasuk kolom opsional.
     *
     * @return array<int, string>
     */
    public function kolom(): array
    {
        return match ($this) {
            self::Pembiayaan => ['nokontrak', 'nocif', 'nama', 'kdprd', 'kdloc', 'pokpby', 'gunadeb', 'tglwo'],
            self::History => ['nokontrak', 'kdprd', 'kdloc', 'pokpby', 'tglexp', 'osmdlc', 'osmgnc', 'tgkmdl', 'tgkmgn', 'haritgk', 'col', 'stsrec', 'stsacc', 'ppka', 'periode'],
            self::Jaminan => ['nokontrak', 'noreg', 'urut', 'tgltaks', 'jnsjamin', 'nominallikuid'],
            self::Kantor => ['kdloc', 'nama', 'alamat'],
            self::Produk => ['kdprd', 'nama', 'pokpby', 'aktif'],
            self::JaminanSetup => ['kdjam', 'ket', 'bobot'],
        };
    }

    /**
     * Kolom yang boleh ada di template namun tidak wajib ada saat diunggah.
     *
     * @return array<int, string>
     */
    public function kolomOpsional(): array
    {
        return match ($this) {
            self::Pembiayaan, self::History, self::Jaminan, self::Kantor, self::JaminanSetup => [],
            self::Produk => ['aktif'],
        };
    }

    /**
     * Kolom yang wajib ada sebagai judul pada file unggahan.
     *
     * @return array<int, string>
     */
    public function kolomWajib(): array
    {
        return array_values(array_diff($this->kolom(), $this->kolomOpsional()));
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
