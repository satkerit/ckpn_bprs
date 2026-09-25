<?php

namespace App\Models;

use App\Enums\MetodeCkpn;
use App\Enums\MetodeLgd;
use App\Enums\StatusRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CkpnRun extends Model
{
    use SoftDeletes;

    protected $table = 'ckpn_run';

    protected $fillable = [
        'periode', 'lookback_bulan', 'metode', 'metode_lgd', 'status', 'total_ead',
        'total_ckpn', 'total_ppka', 'cadangan_ppka_tambahan', 'started_at', 'finished_at', 'created_by', 'log',
    ];

    protected function casts(): array
    {
        return [
            'metode' => MetodeCkpn::class,
            'metode_lgd' => MetodeLgd::class,
            'status' => StatusRun::class,
            'lookback_bulan' => 'integer',
            'total_ead' => 'decimal:2',
            'total_ckpn' => 'decimal:2',
            'total_ppka' => 'decimal:2',
            'cadangan_ppka_tambahan' => 'decimal:2',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(CkpnSegment::class);
    }

    public function hasil(): HasMany
    {
        return $this->hasMany(CkpnHasil::class);
    }

    public function isFinished(): bool
    {
        return $this->status === StatusRun::Done;
    }
}
