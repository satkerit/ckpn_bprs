<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnMatrix extends Model
{
    protected $table = 'ckpn_matrix';

    protected $fillable = ['ckpn_run_id', 'metode', 'segment_key', 'bucket_asal', 'bucket_tujuan', 'probabilitas', 'sumber_n_matrix'];

    protected function casts(): array
    {
        return ['probabilitas' => 'decimal:6', 'sumber_n_matrix' => 'integer'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CkpnRun::class, 'ckpn_run_id');
    }
}
