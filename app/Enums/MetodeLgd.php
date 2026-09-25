<?php

namespace App\Enums;

/**
 * Metode perhitungan LGD.
 */
enum MetodeLgd: string
{
    case Shortfall = 'shortfall';
    case ExpectedRecovery = 'expected_recovery';

    public function label(): string
    {
        return match ($this) {
            self::Shortfall => 'LGD Collateral Shortfall',
            self::ExpectedRecovery => 'LGD Expected Recovery',
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
