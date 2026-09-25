<?php

namespace App\Services\Ckpn;

use App\Enums\DimensiSegmentasi;
use InvalidArgumentException;

class SegmentKeyBuilder
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string|DimensiSegmentasi>  $dimensions
     */
    public function build(array $attributes, array $dimensions): string
    {
        return collect($dimensions)
            ->map(function (string|DimensiSegmentasi $dimension) use ($attributes): string {
                $key = $dimension instanceof DimensiSegmentasi ? $dimension->value : $dimension;

                if (! in_array($key, array_column(DimensiSegmentasi::cases(), 'value'), true)) {
                    throw new InvalidArgumentException("Dimensi segmentasi tidak valid: {$key}");
                }

                return $key.'='.trim((string) ($attributes[$key] ?? ''));
            })
            ->implode('|');
    }
}
