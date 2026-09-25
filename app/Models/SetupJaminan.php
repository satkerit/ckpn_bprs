<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class SetupJaminan extends Model
{
    use HasFactory;

    protected $table = 'setup_jaminan';

    public $incrementing = false;

    protected $primaryKey = 'kdjam';

    protected $keyType = 'string';

    protected $fillable = ['kdjam', 'ket', 'bobot'];

    protected function casts(): array
    {
        return [
            'bobot' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (): void {
            Cache::forget('setup_jaminan.all');
            Cache::forget('setup_jaminan.options');
        });
        static::deleted(function (): void {
            Cache::forget('setup_jaminan.all');
            Cache::forget('setup_jaminan.options');
        });
    }

    /**
     * Relasi ke agunan berdasarkan jenis jaminan (jnsjamin).
     */
    public function agunan(): HasMany
    {
        return $this->hasMany(Agunan::class, 'jnsjamin', 'kdjam');
    }
}
