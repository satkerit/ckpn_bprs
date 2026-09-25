<?php

namespace App\Models;

use App\Enums\JenisUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadBatch extends Model
{
    protected $table = 'upload_batch';

    protected $fillable = [
        'jenis', 'periode', 'file_name', 'file_path',
        'total_baris', 'baris_diproses', 'baris_sukses', 'baris_gagal', 'baris_duplikat',
        'status', 'error_log', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisUpload::class,
            'error_log' => 'array',
            'total_baris' => 'integer',
            'baris_diproses' => 'integer',
            'baris_sukses' => 'integer',
            'baris_gagal' => 'integer',
            'baris_duplikat' => 'integer',
        ];
    }

    /** Persen kemajuan import (0-100). */
    public function progressPersen(): int
    {
        if ($this->total_baris <= 0) {
            return $this->status === 'processing' ? 0 : 100;
        }

        return (int) min(100, round($this->baris_diproses / $this->total_baris * 100));
    }

    public function isSelesai(): bool
    {
        return in_array($this->status, ['completed', 'completed_with_errors', 'failed'], true);
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
