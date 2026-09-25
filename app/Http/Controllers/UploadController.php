<?php

namespace App\Http\Controllers;

use App\Enums\JenisUpload;
use App\Exports\UploadErrorExport;
use App\Jobs\ProcessUploadBatch;
use App\Models\UploadBatch;
use App\Services\Upload\ImportDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadController extends Controller
{
    public function __construct(private readonly ImportDataService $importDataService) {}

    public function riwayat(): View
    {
        Gate::authorize('dashboard.view');

        return view('dashboard.upload-riwayat', [
            'title' => 'Riwayat Upload',
            'batches' => UploadBatch::query()->with('pengunggah')->latest()->paginate(20),
        ]);
    }

    public function errorReport(Request $request, UploadBatch $batch): BinaryFileResponse
    {
        Gate::authorize('dashboard.view');

        abort_if(empty($batch->error_log), 404, 'Batch ini tidak memiliki baris gagal.');

        $format = $request->query('format') === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        $extension = $format === ExcelWriter::CSV ? 'csv' : 'xlsx';

        return Excel::download(
            new UploadErrorExport($batch),
            'error_upload_'.$batch->id.'.'.$extension,
            $format,
        );
    }

    public function status(UploadBatch $batch): JsonResponse
    {
        Gate::authorize('dashboard.view');

        return response()->json([
            'id' => $batch->id,
            'status' => $batch->status,
            'jenis' => ['value' => $batch->jenis->value, 'label' => $batch->jenis->label()],
            'file_name' => $batch->file_name,
            'progress_persen' => $batch->progressPersen(),
            'total_baris' => $batch->total_baris,
            'baris_diproses' => $batch->baris_diproses,
            'baris_sukses' => $batch->baris_sukses,
            'baris_duplikat' => $batch->baris_duplikat,
            'baris_gagal' => $batch->baris_gagal,
            'is_selesai' => $batch->isSelesai(),
            'error_log' => $batch->error_log ? array_slice($batch->error_log, 0, 15) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'jenis' => ['required', 'string'],
            'file' => [
                'required',
                'file',
                // Validasi ekstensi (client-side hint)
                'mimes:xlsx,xls,csv',
                // Validasi tipe konten aktual (server-side, mencegah file disguise)
                'mimetypes:text/csv,application/csv,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'max:51200',
            ],
        ]);
        $jenisUpload = JenisUpload::tryFrom($data['jenis']);

        abort_if($jenisUpload === null, 422, 'Jenis upload tidak valid.');
        Gate::authorize('upload.'.$jenisUpload->value);

        $uploadedFile = $request->file('file');

        // Simpan file ke storage/app/uploads/ agar bisa dibaca oleh Job di queue.
        $storagePath = $uploadedFile->store('uploads', 'local');

        // Buat UploadBatch dengan status 'queued' dan simpan path file sementara.
        $batch = UploadBatch::query()->create([
            'jenis' => $jenisUpload,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $storagePath,
            'status' => 'queued',
            'uploaded_by' => auth()->id(),
        ]);

        // Dispatch Job ke queue; proses import berjalan di background.
        ProcessUploadBatch::dispatch($batch->id);

        if ($request->wantsJson()) {
            return response()->json([
                'batch_id' => $batch->id,
                'status' => $batch->status,
                'message' => 'Upload diterima dan sedang diproses. Pantau status dengan batch_id.',
                'status_url' => route('upload.status', $batch),
            ], Response::HTTP_ACCEPTED);
        }

        return back()->with('status', 'File berhasil diunggah dan sedang diproses. Refresh halaman untuk melihat hasilnya.');
    }
}
