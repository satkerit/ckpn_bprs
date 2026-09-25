<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ProdukPembiayaan extends Model
{
    protected $table = 'produk_pembiayaan';

    public $incrementing = false;

    protected $primaryKey = 'kdprd';

    protected $keyType = 'string';

    protected $fillable = ['kdprd', 'nama', 'pokpby', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (): void {
            Cache::forget('produk_pembiayaan.all');
            Cache::forget('produk_pembiayaan.options');
        });
        static::deleted(function (): void {
            Cache::forget('produk_pembiayaan.all');
            Cache::forget('produk_pembiayaan.options');
        });
    }

    /** Kode akad (pokpby) yang juga dipakai tabel pembiayaan. */
    public function akad(): BelongsTo
    {
        return $this->belongsTo(KodeAkad::class, 'pokpby', 'pokpby');
    }
}
