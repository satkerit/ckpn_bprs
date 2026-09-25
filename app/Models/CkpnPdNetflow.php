<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnPdNetflow extends Model
{
    protected $table = 'ckpn_pd_netflow';

    protected $fillable = ['ckpn_run_id', 'segment_key', 'bucket_asal', 'pd_1_bulan', 'pd_kumulatif', 'netflow_to_loss'];

    protected function casts(): array
    {
        return ['pd_1_bulan' => 'decimal:6', 'pd_kumulatif' => 'decimal:6', 'netflow_to_loss' => 'decimal:6'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CkpnRun::class, 'ckpn_run_id');
    }
}
