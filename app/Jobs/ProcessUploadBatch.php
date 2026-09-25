<?php

namespace App\Jobs;

use App\Models\UploadBatch;
use App\Services\Upload\ImportDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUploadBatch implements ShouldQueue
{
    use Queueable;

    /** Jumlah percobaan maksimal jika job gagal. */
    public int $tries = 1;

    /** Timeout proses import dalam detik (30 menit) untuk mendukung upload >100.000 baris. */
    public int $timeout = 1800;

    public function __construct(public int $batchId) {}

    public function handle(ImportDataService $importDataService): void
    {
        // Hindari query logging yang menumpuk di memori worker saat memproses ribuan baris.
        DB::disableQueryLog();
        @ini_set('memory_limit', '1024M');

        $batch = UploadBatch::query()->findOrFail($this->batchId);

        // Hindari re-proses batch yang sudah selesai atau sedang diproses.
        if ($batch->isSelesai()) {
            return;
        }

        $storagePath = $batch->file_path;

        if (! $storagePath || ! Storage::exists($storagePath)) {
            $batch->update([
                'status' => 'failed',
                'error_log' => [['message' => 'File upload tidak ditemukan di storage: '.($storagePath ?? '(null)')]],
            ]);

            return;
        }

        $batch->update(['status' => 'processing']);

        $absolutePath = Storage::path($storagePath);
        $jenisUpload = $batch->jenis;

        try {
            // Buat UploadedFile dari file yang sudah disimpan di storage.
            $uploadedFile = new UploadedFile(
                $absolutePath,
                $batch->file_name,
                null,
                null,
                true, // test mode: bypass is_uploaded_file() check
            );

            $importDataService->importFromBatch($uploadedFile, $jenisUpload, $batch);
        } catch (\Throwable $exception) {
            Log::error('ProcessUploadBatch gagal', [
                'batch_id' => $this->batchId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $batch->update([
                'status' => 'failed',
                'error_log' => [['message' => 'Job error: '.$exception->getMessage()]],
            ]);
        } finally {
            // Hapus file sementara setelah proses selesai (berhasil atau gagal).
            if ($storagePath && Storage::exists($storagePath)) {
                Storage::delete($storagePath);
            }

            gc_collect_cycles();
        }
    }
}
