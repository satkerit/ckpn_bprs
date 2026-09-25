<?php

namespace App\Models;

use App\Enums\StatusPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnPeriode extends Model
{
    protected $table = 'ckpn_periode';

    protected $fillable = ['periode', 'status', 'keterangan', 'created_by', 'locked_at'];

    protected function casts(): array
    {
        return [
            'status' => StatusPeriode::class,
            'locked_at' => 'datetime',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /** Contoh format tampilan: "Sep 2026" dari "202609". */
    public function label(): string
    {
        $date = \DateTime::createFromFormat('Ym', $this->periode);

        return $date ? $date->format('M Y') : $this->periode;
    }
}
