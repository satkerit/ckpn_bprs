<?php

namespace App\Enums;

/**
 * Klasifikasi CKPN: individual (spesifik) atau kolektif.
 */
enum TipeCkpn: string
{
    case Individual = 'individual';
    case Kolektif = 'kolektif';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'CKPN Individual',
            self::Kolektif => 'CKPN Kolektif',
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
