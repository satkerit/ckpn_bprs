<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CkpnSegment extends Model
{
    protected $table = 'ckpn_segment';

    protected $fillable = ['ckpn_run_id', 'urutan', 'dimensi', 'nilai', 'parent_id'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CkpnRun::class, 'ckpn_run_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
