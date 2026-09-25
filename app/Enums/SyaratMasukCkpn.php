<?php

namespace App\Enums;

/**
 * Syarat sebuah pembiayaan masuk populasi perhitungan CKPN dan snapshot
 * seluruh metode PD (Netflow dan Migration), ditentukan per kode akad
 * (pokpby) pada setup role CKPN.
 */
enum SyaratMasukCkpn: string
{
    /** Selalu masuk selama rekening aktif dan belum write-off. */
    case Selalu = 'selalu';

    /** Masuk hanya bila ada tunggakan pokok (tgkmdl > 0). */
    case AdaTunggakan = 'ada_tunggakan';

    /**
     * Masuk hanya bila sudah jatuh tempo: ada hari tunggakan, ada tunggakan
     * pokok/margin, atau tanggal jatuh tempo (tglexp) sudah lewat akhir bulan
     * periode snapshot.
     */
    case JatuhTempo = 'jatuh_tempo';

    public function label(): string
    {
        return match ($this) {
            self::Selalu => 'Selalu masuk CKPN',
            self::AdaTunggakan => 'Masuk bila ada tunggakan pokok (tgkmdl)',
            self::JatuhTempo => 'Masuk bila sudah jatuh tempo',
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
