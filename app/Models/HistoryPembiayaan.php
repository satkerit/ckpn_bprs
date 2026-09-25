<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoryPembiayaan extends Model
{
    use SoftDeletes;

    protected $table = 'history_pembiayaan';

    protected $fillable = [
        'nokontrak', 'kdprd', 'kdloc', 'pokpby', 'tglexp',
        'osmdlc', 'osmgnc', 'tgkmdl', 'tgkmgn',
        'haritgk', 'col', 'stsrec', 'stsacc', 'ppka', 'periode',
    ];

    protected $dates = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'osmdlc' => 'decimal:2',
            'osmgnc' => 'decimal:2',
            'tgkmdl' => 'decimal:2',
            'tgkmgn' => 'decimal:2',
            'ppka' => 'decimal:2',
            'col' => 'integer',
            'haritgk' => 'integer',
        ];
    }

    public function pembiayaan(): BelongsTo
    {
        return $this->belongsTo(Pembiayaan::class, 'nokontrak', 'nokontrak');
    }

    public function akad(): BelongsTo
    {
        return $this->belongsTo(KodeAkad::class, 'pokpby', 'pokpby');
    }

    public function isWriteoff(): bool
    {
        return $this->stsacc === 'W';
    }

    public function isAktif(): bool
    {
        return $this->stsrec !== null && strtoupper($this->stsrec) !== 'W';
    }

    /** Baki debet: sisa pokok + seluruh tunggakan. */
    public function bakiDebet(): float
    {
        return (float) $this->osmdlc + (float) $this->tgkmdl + (float) $this->tgkmgn;
    }
}
