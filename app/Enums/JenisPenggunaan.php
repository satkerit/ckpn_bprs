<?php

namespace App\Enums;

/**
 * Jenis penggunaan pembiayaan (kolom `gunadeb`).
 */
enum JenisPenggunaan: string
{
    case ModalKerja = '1';
    case Investasi = '2';
    case Konsumtif = '3';

    public function label(): string
    {
        return match ($this) {
            self::ModalKerja => 'Modal Kerja',
            self::Investasi => 'Investasi',
            self::Konsumtif => 'Konsumtif',
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
