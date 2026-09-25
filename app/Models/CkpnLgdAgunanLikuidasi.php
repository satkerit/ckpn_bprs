<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnLgdAgunanLikuidasi extends Model
{
    protected $table = 'ckpn_lgd_agunan_likuidasi';

    protected $fillable = ['periode', 'nokontrak', 'nilai_eksekusi', 'nilai_estimasi', 'sumber', 'biaya_terkait', 'tanggal_eksekusi'];

    protected function casts(): array
    {
        return ['nilai_eksekusi' => 'decimal:2', 'nilai_estimasi' => 'decimal:2', 'biaya_terkait' => 'decimal:2', 'tanggal_eksekusi' => 'date'];
    }

    public function pembiayaan(): BelongsTo
    {
        return $this->belongsTo(Pembiayaan::class, 'nokontrak', 'nokontrak');
    }
}
