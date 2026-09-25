<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CkpnBucket extends Model
{
    protected $table = 'ckpn_bucket';

    protected $fillable = ['kode', 'label', 'deskripsi', 'urutan', 'is_default'];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'is_default' => 'boolean',
        ];
    }
}
