<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnHasil extends Model
{
    protected $table = 'ckpn_hasil';

    protected $fillable = [
        'ckpn_run_id', 'periode', 'nokontrak', 'nocif', 'nama', 'kdloc', 'pokpby', 'kdprd', 'gunadeb',
        'segment_key', 'tipe_ckpn', 'in_scope_psak414', 'bucket', 'ead', 'pd_netflow', 'lgd_persen',
        'ckpn_psak414', 'ppka_wajib', 'selisih', 'cadangan_tambahan',
    ];

    protected function casts(): array
    {
        return [
            'in_scope_psak414' => 'boolean',
            'ead' => 'decimal:2',
            'pd_netflow' => 'decimal:6',
            'lgd_persen' => 'decimal:6',
            'ckpn_psak414' => 'decimal:2',
            'ppka_wajib' => 'decimal:2',
            'selisih' => 'decimal:2',
            'cadangan_tambahan' => 'decimal:2',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CkpnRun::class, 'ckpn_run_id');
    }

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'kdloc', 'kdloc');
    }
}
