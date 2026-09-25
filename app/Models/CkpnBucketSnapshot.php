<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnBucketSnapshot extends Model
{
    protected $table = 'ckpn_bucket_snapshot';

    protected $fillable = ['ckpn_run_id', 'periode', 'nokontrak', 'bucket', 'saldo_agregat', 'hari_tunggakan', 'col'];

    protected function casts(): array
    {
        return ['saldo_agregat' => 'decimal:2', 'hari_tunggakan' => 'integer', 'col' => 'integer'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CkpnRun::class, 'ckpn_run_id');
    }
}
