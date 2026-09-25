<?php

namespace App\Services\Ckpn;

class BucketClassifier
{
    /** Default thresholds mirroring config/ckpn.php bucket_haritgk. */
    private const DEFAULT_THRESHOLDS = [
        2 => 1,
        3 => 30,
        4 => 60,
        5 => 90,
        6 => 120,
        7 => 150,
        8 => 180,
        9 => 210,
        10 => 240,
        11 => 270,
        12 => 300,
        13 => 330,
    ];

    public function __construct(private array $thresholds = [])
    {
        if ($this->thresholds === []) {
            $this->thresholds = self::DEFAULT_THRESHOLDS;
        }
    }

    public function classify(?int $hariTunggakan, ?string $statusAkad = null, bool $writeoff = false): string
    {
        if ($writeoff) {
            return $this->setting('bucket_wo', 'WO');
        }

        if ($statusAkad !== null && strtoupper($statusAkad) === 'PO') {
            return $this->setting('bucket_po', 'PO');
        }

        $hariTunggakan = max(0, $hariTunggakan ?? 0);

        foreach ($this->thresholds() as $bucket => $minimumHari) {
            if ($hariTunggakan < $minimumHari) {
                return (string) ((int) $bucket - 1);
            }
        }

        return '14';
    }

    private function thresholds(): array
    {
        return $this->thresholds;
    }

    private function setting(string $key, string $fallback): string
    {
        return function_exists('app') && app()->bound('config')
            ? (string) config("ckpn.perhitungan.{$key}", $fallback)
            : $fallback;
    }
}
