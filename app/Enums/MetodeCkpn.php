<?php

namespace App\Enums;

/**
 * Metode perhitungan CKPN kolektif.
 */
enum MetodeCkpn: string
{
    case Netflow = 'netflow';
    case Migration = 'migration';

    public function label(): string
    {
        return match ($this) {
            self::Netflow => 'PD Netflow',
            self::Migration => 'PD Migration',
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
