<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $fillable = [
        'user_id', 'aksi', 'model', 'model_id',
        'perubahan', 'old_values', 'new_values', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'perubahan' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat aktivitas pengguna.
     *
     * @param  string  $aksi  Nama aksi, misal: 'user.created', 'auth.login'.
     * @param  Model|null  $model  Model yang terdampak (opsional).
     * @param  array  $perubahan  Ringkasan perubahan bebas format (opsional).
     * @param  array  $oldValues  Snapshot nilai sebelum perubahan (opsional).
     * @param  array  $newValues  Snapshot nilai sesudah perubahan (opsional).
     */
    public static function catat(
        string $aksi,
        ?Model $model = null,
        array $perubahan = [],
        array $oldValues = [],
        array $newValues = [],
    ): void {
        static::query()->create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'model' => $model ? $model->getMorphClass() : null,
            'model_id' => $model?->getKey(),
            'perubahan' => $perubahan ?: null,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Catat perubahan model dengan snapshot before/after secara otomatis.
     * Gunakan sebelum dan sesudah Model::update() dipanggil:
     *
     *   $old = $model->getAttributes();
     *   $model->update($data);
     *   AuditLog::catAtPerubahan('record.updated', $model, $old, $model->getChanges());
     *
     * @param  array  $oldAttributes  Nilai atribut sebelum update (dari getAttributes()).
     * @param  array  $newAttributes  Nilai atribut sesudah update (dari getChanges()).
     */
    public static function catatPerubahan(
        string $aksi,
        Model $model,
        array $oldAttributes = [],
        array $newAttributes = [],
    ): void {
        // Saring hanya kolom yang benar-benar berubah
        $changed = array_keys($newAttributes);
        $oldFiltered = array_intersect_key($oldAttributes, array_flip($changed));

        static::catat($aksi, $model, [], $oldFiltered, $newAttributes);
    }
}
