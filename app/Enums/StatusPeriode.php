<?php

namespace App\Enums;

/**
 * Status periode penilaian CKPN.
 */
enum StatusPeriode: string
{
    case Draft = 'draft';
    case Locked = 'locked';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Locked => 'Terkunci',
            self::Final => 'Final',
        };
    }

    public function isLocked(): bool
    {
        return $this !== self::Draft;
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
