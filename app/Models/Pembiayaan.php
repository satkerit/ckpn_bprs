<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembiayaan extends Model
{
    use SoftDeletes;

    protected $table = 'pembiayaan';

    /** Master pembiayaan ber-key natural: nokontrak. */
    public $incrementing = false;

    protected $primaryKey = 'nokontrak';

    protected $keyType = 'string';

    protected $fillable = [
        'nokontrak', 'nocif', 'nama', 'kdprd', 'kdloc', 'pokpby', 'gunadeb', 'tglwo',
    ];

    protected $dates = ['deleted_at'];

    protected function casts(): array
    {
        return [];
    }

    public function histories(): HasMany
    {
        return $this->hasMany(HistoryPembiayaan::class, 'nokontrak');
    }

    public function agunan(): HasMany
    {
        return $this->hasMany(Agunan::class, 'nokontrak');
    }

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'kdloc', 'kdloc');
    }

    public function akad(): BelongsTo
    {
        return $this->belongsTo(KodeAkad::class, 'pokpby', 'pokpby');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(ProdukPembiayaan::class, 'kdprd', 'kdprd');
    }

    public function isWriteoff(): bool
    {
        return $this->tglwo !== null && $this->tglwo !== '';
    }
}
