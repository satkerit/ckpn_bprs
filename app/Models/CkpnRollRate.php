<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnRollRate extends Model
{
    protected $table = 'ckpn_roll_rate';

    protected $fillable = ['ckpn_run_id', 'periode_asal', 'periode_tujuan', 'segment_key', 'bucket_asal', 'bucket_tujuan', 'jumlah_rekening', 'total_saldo'];

    protected function casts(): array
    {
        return ['jumlah_rekening' => 'integer', 'total_saldo' => 'decimal:2'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CkpnRun::class, 'ckpn_run_id');
    }
}
