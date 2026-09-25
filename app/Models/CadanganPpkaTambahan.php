<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CadanganPpkaTambahan extends Model
{
    protected $table = 'cadangan_ppka_tambahan';

    protected $fillable = ['ckpn_run_id', 'periode', 'total_ckpn', 'total_ppka', 'nilai_cadangan', 'dibentuk_pada', 'keterangan'];

    protected function casts(): array
    {
        return [
            'total_ckpn' => 'decimal:2',
            'total_ppka' => 'decimal:2',
            'nilai_cadangan' => 'decimal:2',
            'dibentuk_pada' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CkpnRun::class, 'ckpn_run_id');
    }
}
