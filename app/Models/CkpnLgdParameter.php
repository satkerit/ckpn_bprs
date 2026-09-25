<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CkpnLgdParameter extends Model
{
    protected $table = 'ckpn_lgd_parameter';

    protected $fillable = ['periode', 'metode', 'jnsjamin', 'segment_key', 'haircut_persen', 'biaya_lelang_persen', 'berlaku_dari'];

    protected function casts(): array
    {
        return ['haircut_persen' => 'decimal:4', 'biaya_lelang_persen' => 'decimal:4'];
    }
}
