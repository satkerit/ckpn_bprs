<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnLgdHasil extends Model
{
    protected $table = 'ckpn_lgd_hasil';

    protected $fillable = ['ckpn_run_id', 'metode', 'segment_key', 'jnsjamin', 'total_ead_default', 'total_penerimaan', 'shortfall', 'lgd_persen', 'jumlah_debitur_default'];

    protected function casts(): array
    {
        return ['total_ead_default' => 'decimal:2', 'total_penerimaan' => 'decimal:2', 'shortfall' => 'decimal:2', 'lgd_persen' => 'decimal:6', 'jumlah_debitur_default' => 'integer'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CkpnRun::class, 'ckpn_run_id');
    }
}
