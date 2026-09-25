<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetupParameter extends Model
{
    protected $table = 'setup_parameter';

    protected $fillable = ['kunci', 'nilai', 'group_key', 'keterangan'];

    /**
     * Ambil nilai parameter; kembalikan fallback bila belum ada di DB.
     */
    public static function get(string $kunci, mixed $default = null): mixed
    {
        $row = static::query()->where('kunci', $kunci)->first();

        if ($row === null || $row->nilai === null) {
            return $default;
        }

        // Nilai disimpan serialisasi JSON agar dapat berupa array/angka/bool.
        $decoded = json_decode($row->nilai, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->nilai;
    }

    public static function set(string $kunci, mixed $nilai, string $group = 'umum', ?string $keterangan = null): void
    {
        static::query()->updateOrCreate(
            ['kunci' => $kunci],
            [
                'nilai' => json_encode($nilai, JSON_UNESCAPED_UNICODE),
                'group_key' => $group,
                'keterangan' => $keterangan,
            ],
        );
    }
}
