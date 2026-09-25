<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agunan extends Model
{
    use SoftDeletes;

    protected $table = 'agunan';

    protected $fillable = [
        'nokontrak', 'noreg', 'urut', 'tgltaks', 'jnsjamin', 'nominallikuid',
    ];

    protected $dates = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'urut' => 'integer',
            'nominallikuid' => 'decimal:2',
        ];
    }

    public function pembiayaan(): BelongsTo
    {
        return $this->belongsTo(Pembiayaan::class, 'nokontrak', 'nokontrak');
    }

    /**
     * Relasi ke setup jaminan (master jenis jaminan).
     */
    public function setupJaminan(): BelongsTo
    {
        return $this->belongsTo(SetupJaminan::class, 'jnsjamin', 'kdjam');
    }
}
