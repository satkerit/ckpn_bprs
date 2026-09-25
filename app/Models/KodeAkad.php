<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class KodeAkad extends Model
{
    use SoftDeletes;

    protected $table = 'kode_akad';

    public $incrementing = false;

    protected $primaryKey = 'pokpby';

    protected $keyType = 'string';

    protected $fillable = ['pokpby', 'nama', 'skema', 'aktif'];

    protected $dates = ['deleted_at'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (): void {
            Cache::forget('kode_akad.options');
            Cache::forget('kode_akad.all');
        });
        static::deleted(function (): void {
            Cache::forget('kode_akad.options');
            Cache::forget('kode_akad.all');
        });
    }

    /** Produk pembiayaan yang memakai kode akad ini. */
    public function produk(): HasMany
    {
        return $this->hasMany(ProdukPembiayaan::class, 'pokpby', 'pokpby');
    }

    /** Pembiayaan yang memakai kode akad ini. */
    public function pembiayaan(): HasMany
    {
        return $this->hasMany(Pembiayaan::class, 'pokpby', 'pokpby');
    }

    /** History pembiayaan yang memakai kode akad ini. */
    public function history(): HasMany
    {
        return $this->hasMany(HistoryPembiayaan::class, 'pokpby', 'pokpby');
    }

    /** Jumlah data yang memakai kode akad ini, untuk penjagaan sebelum hapus. */
    public function jumlahPemakai(): int
    {
        return $this->produk()->count() + $this->pembiayaan()->count() + $this->history()->count();
    }

    /**
     * Daftar kode akad untuk pilihan pada form, dengan label kode + nama.
     *
     * Hanya kode akad aktif yang ditawarkan untuk data baru; kode akad yang
     * sudah dinonaktifkan tetapi masih dipakai data lama tetap disertakan agar
     * form edit tidak kehilangan nilai yang sedang tersimpan.
     *
     * @return array<string, string>
     */
    public static function options(?string ...$tetapDisertakan): array
    {
        $tetapDisertakan = array_values(array_filter($tetapDisertakan));

        return static::query()
            ->where(fn ($query) => $query
                ->where('aktif', true)
                ->orWhereIn('pokpby', $tetapDisertakan))
            ->orderBy('pokpby')
            ->pluck('nama', 'pokpby')
            ->map(fn (string $nama, string $pokpby): string => $pokpby.' — '.$nama)
            ->all();
    }
}
