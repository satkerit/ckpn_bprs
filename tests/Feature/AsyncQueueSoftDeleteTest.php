<?php

namespace Tests\Feature;

use App\Enums\JenisUpload;
use App\Jobs\ProcessUploadBatch;
use App\Models\Agunan;
use App\Models\HistoryPembiayaan;
use App\Models\KodeAkad;
use App\Models\Pembiayaan;
use App\Models\UploadBatch;
use App\Models\User;
use App\Services\Upload\ImportDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Task 6 & 7: Pengujian Async Upload Queue dan SoftDeletes.
 */
class AsyncQueueSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Permission yang dibutuhkan untuk upload dan view dashboard.
        $permissions = [
            'dashboard.view',
            'upload.pembiayaan',
            'upload.history',
            'upload.jaminan',
            'upload.kantor',
            'upload.produk',
            'upload.jaminan_setup',
        ];

        foreach ($permissions as $perm) {
            $this->user->givePermissionTo(
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web'])
            );
        }
    }

    // =========================================================================
    // Task 6: Async Upload Queue
    // =========================================================================

    /**
     * Upload harus segera mengembalikan 202 Accepted + batch_id
     * tanpa menunggu proses import selesai.
     */
    public function test_upload_dispatches_job_and_returns_queued_status(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('template.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->user)
            ->postJson('/upload', [
                'jenis' => JenisUpload::Pembiayaan->value,
                'file' => $file,
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['batch_id', 'status', 'message', 'status_url'])
            ->assertJsonFragment(['status' => 'queued']);

        // Pastikan job di-dispatch ke queue (bukan dieksekusi sinkronus).
        Queue::assertPushed(ProcessUploadBatch::class, function (ProcessUploadBatch $job) use ($response): bool {
            return $job->batchId === $response->json('batch_id');
        });
    }

    /**
     * Setelah upload, batch harus ada di DB dengan status 'queued'
     * dan file_path yang menunjuk ke storage.
     */
    public function test_upload_creates_batch_with_queued_status_and_file_path(): void
    {
        Queue::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('data.xlsx', 5);

        $response = $this->actingAs($this->user)
            ->postJson('/upload', [
                'jenis' => JenisUpload::Pembiayaan->value,
                'file' => $file,
            ]);

        $batchId = $response->json('batch_id');
        $batch = UploadBatch::find($batchId);

        $this->assertNotNull($batch, 'Batch harus disimpan ke database.');
        $this->assertSame('queued', $batch->status);
        $this->assertNotNull($batch->file_path, 'file_path harus terisi.');
        $this->assertSame('data.xlsx', $batch->file_name);
        $this->assertSame($this->user->id, $batch->uploaded_by);

        // File harus tersimpan di storage.
        Storage::disk('local')->assertExists($batch->file_path);
    }

    /**
     * Endpoint status batch harus mengembalikan informasi lengkap batch.
     */
    public function test_batch_status_endpoint_returns_batch_info(): void
    {
        $batch = UploadBatch::create([
            'jenis' => JenisUpload::Pembiayaan,
            'file_name' => 'test.xlsx',
            'status' => 'queued',
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/upload/batch/'.$batch->id.'/status');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $batch->id,
                'status' => 'queued',
            ])
            ->assertJsonStructure([
                'id', 'status', 'jenis', 'file_name',
                'progress_persen', 'total_baris', 'baris_sukses',
                'baris_gagal', 'is_selesai',
            ]);
    }

    /**
     * Setelah job selesai dengan sukses, status batch harus 'completed'.
     */
    public function test_batch_status_becomes_completed_after_job_finishes(): void
    {
        $batch = UploadBatch::create([
            'jenis' => JenisUpload::Pembiayaan,
            'file_name' => 'test.xlsx',
            'status' => 'queued',
            'uploaded_by' => $this->user->id,
        ]);

        // Simulasi Job menyelesaikan proses import dengan sukses.
        $batch->update([
            'status' => 'completed',
            'total_baris' => 5,
            'baris_diproses' => 5,
            'baris_sukses' => 5,
            'baris_gagal' => 0,
            'baris_duplikat' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/upload/batch/'.$batch->id.'/status');

        $response->assertOk()
            ->assertJsonFragment(['status' => 'completed', 'is_selesai' => true])
            ->assertJsonFragment(['baris_sukses' => 5]);
    }

    /**
     * Setelah job gagal, status batch harus 'failed'.
     */
    public function test_batch_status_becomes_failed_when_job_encounters_error(): void
    {
        $batch = UploadBatch::create([
            'jenis' => JenisUpload::Pembiayaan,
            'file_name' => 'rusak.xlsx',
            'status' => 'queued',
            'uploaded_by' => $this->user->id,
        ]);

        // Simulasi Job gagal.
        $batch->update([
            'status' => 'failed',
            'error_log' => [['message' => 'File tidak ditemukan di storage.']],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/upload/batch/'.$batch->id.'/status');

        $response->assertOk()
            ->assertJsonFragment(['status' => 'failed', 'is_selesai' => true]);
    }

    /**
     * Job ProcessUploadBatch harus update status ke 'failed' jika file_path tidak ada.
     */
    public function test_process_upload_batch_job_marks_failed_when_file_missing(): void
    {
        $batch = UploadBatch::create([
            'jenis' => JenisUpload::Pembiayaan,
            'file_name' => 'missing.xlsx',
            'file_path' => 'uploads/tidak_ada.xlsx',
            'status' => 'queued',
            'uploaded_by' => $this->user->id,
        ]);

        // Jalankan job secara sinkronus dalam test.
        $job = new ProcessUploadBatch($batch->id);
        $job->handle(app(ImportDataService::class));

        $batch->refresh();

        $this->assertSame('failed', $batch->status);
        $this->assertNotEmpty($batch->error_log);
        $this->assertStringContainsString('tidak ditemukan', $batch->error_log[0]['message']);
    }

    /**
     * Job tidak memproses ulang batch yang sudah selesai (idempotent).
     */
    public function test_process_upload_batch_job_skips_already_completed_batch(): void
    {
        $batch = UploadBatch::create([
            'jenis' => JenisUpload::Pembiayaan,
            'file_name' => 'done.xlsx',
            'status' => 'completed',
            'baris_sukses' => 3,
            'uploaded_by' => $this->user->id,
        ]);

        $job = new ProcessUploadBatch($batch->id);
        $job->handle(app(ImportDataService::class));

        $batch->refresh();

        // Status tidak boleh berubah dari 'completed'.
        $this->assertSame('completed', $batch->status);
        $this->assertSame(3, $batch->baris_sukses);
    }

    // =========================================================================
    // Task 7: SoftDeletes
    // =========================================================================

    /**
     * Pembiayaan yang dihapus tidak muncul di listing normal,
     * tapi masih ada di database dengan deleted_at terisi.
     */
    public function test_pembiayaan_soft_delete_hides_from_listing_but_kept_in_db(): void
    {
        $pembiayaan = Pembiayaan::create([
            'nokontrak' => 'KTR-001',
            'nocif' => 'CIF001',
            'nama' => 'Nasabah Uji',
            'kdprd' => 'P001',
            'kdloc' => 'L001',
            'pokpby' => 'AKD01',
            'gunadeb' => 'M',
        ]);

        // Soft delete.
        $pembiayaan->delete();

        // Tidak tampil di query normal.
        $this->assertNull(Pembiayaan::find('KTR-001'));
        $this->assertCount(0, Pembiayaan::all());

        // Masih ada di database dengan deleted_at terisi.
        $deleted = Pembiayaan::withTrashed()->find('KTR-001');
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
        $this->assertTrue($deleted->trashed());
    }

    /**
     * HistoryPembiayaan soft delete: data hilang dari query biasa, tersimpan dengan deleted_at.
     */
    public function test_history_pembiayaan_soft_delete(): void
    {
        $history = HistoryPembiayaan::create([
            'nokontrak' => 'KTR-002',
            'kdprd' => 'P001',
            'kdloc' => 'L001',
            'pokpby' => 'AKD01',
            'osmdlc' => 1000000,
            'osmgnc' => 0,
            'tgkmdl' => 0,
            'tgkmgn' => 0,
            'haritgk' => 0,
            'col' => 1,
            'stsrec' => 'A',
            'ppka' => 0,
            'periode' => '202609',
        ]);

        $id = $history->id;
        $history->delete();

        // Tidak tampil di query normal.
        $this->assertNull(HistoryPembiayaan::find($id));

        // Masih ada dengan deleted_at.
        $deleted = HistoryPembiayaan::withTrashed()->find($id);
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
    }

    /**
     * Agunan soft delete: data hilang dari query biasa, tersimpan dengan deleted_at.
     */
    public function test_agunan_soft_delete(): void
    {
        $agunan = Agunan::create([
            'nokontrak' => 'KTR-003',
            'noreg' => 'AGN001',
            'urut' => 1,
            'jnsjamin' => 'TANAH',
            'nominallikuid' => 50000000,
        ]);

        $id = $agunan->id;
        $agunan->delete();

        $this->assertNull(Agunan::find($id));

        $deleted = Agunan::withTrashed()->find($id);
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
    }

    /**
     * KodeAkad soft delete: data hilang dari query biasa, tersimpan dengan deleted_at.
     */
    public function test_kode_akad_soft_delete(): void
    {
        $kodeAkad = KodeAkad::create([
            'pokpby' => 'AKD99',
            'nama' => 'Akad Uji',
            'skema' => 'margin',
            'aktif' => true,
        ]);

        $kodeAkad->delete();

        // Tidak tampil di query normal.
        $this->assertNull(KodeAkad::find('AKD99'));

        // Masih ada dengan deleted_at.
        $deleted = KodeAkad::withTrashed()->find('AKD99');
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
        $this->assertTrue($deleted->trashed());
    }

    /**
     * Data yang di-softdelete bisa di-restore kembali.
     */
    public function test_pembiayaan_restore_after_soft_delete(): void
    {
        $pembiayaan = Pembiayaan::create([
            'nokontrak' => 'KTR-RESTORE',
            'nocif' => 'CIF999',
            'nama' => 'Nasabah Restore',
            'kdprd' => 'P001',
            'kdloc' => 'L001',
            'pokpby' => 'AKD01',
            'gunadeb' => 'M',
        ]);

        $pembiayaan->delete();
        $this->assertNull(Pembiayaan::find('KTR-RESTORE'));

        // Restore data.
        Pembiayaan::withTrashed()->where('nokontrak', 'KTR-RESTORE')->restore();

        // Setelah restore, data muncul kembali di query normal.
        $restored = Pembiayaan::find('KTR-RESTORE');
        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at);
        $this->assertFalse($restored->trashed());
    }

    /**
     * KodeAkad restore setelah soft delete.
     */
    public function test_kode_akad_restore_after_soft_delete(): void
    {
        KodeAkad::create([
            'pokpby' => 'AKD88',
            'nama' => 'Akad Restore',
            'skema' => 'ujrah',
            'aktif' => true,
        ]);

        KodeAkad::find('AKD88')->delete();
        $this->assertNull(KodeAkad::find('AKD88'));

        KodeAkad::withTrashed()->where('pokpby', 'AKD88')->restore();

        $restored = KodeAkad::find('AKD88');
        $this->assertNotNull($restored);
        $this->assertFalse($restored->trashed());
    }

    /**
     * Soft delete bersifat massal: semua data bisa di-softdelete sekaligus.
     */
    public function test_bulk_soft_delete_pembiayaan(): void
    {
        Pembiayaan::create(['nokontrak' => 'BULK-001', 'nocif' => 'C1', 'nama' => 'N1', 'kdprd' => 'P1', 'kdloc' => 'L1', 'pokpby' => 'A1', 'gunadeb' => 'M']);
        Pembiayaan::create(['nokontrak' => 'BULK-002', 'nocif' => 'C2', 'nama' => 'N2', 'kdprd' => 'P1', 'kdloc' => 'L1', 'pokpby' => 'A1', 'gunadeb' => 'M']);
        Pembiayaan::create(['nokontrak' => 'BULK-003', 'nocif' => 'C3', 'nama' => 'N3', 'kdprd' => 'P1', 'kdloc' => 'L1', 'pokpby' => 'A1', 'gunadeb' => 'M']);

        $this->assertCount(3, Pembiayaan::all());

        // Bulk soft delete.
        Pembiayaan::whereIn('nokontrak', ['BULK-001', 'BULK-002', 'BULK-003'])->delete();

        // Tidak ada di listing normal.
        $this->assertCount(0, Pembiayaan::all());

        // Semua masih ada di DB.
        $this->assertCount(3, Pembiayaan::withTrashed()->whereIn('nokontrak', ['BULK-001', 'BULK-002', 'BULK-003'])->get());

        // Semuanya punya deleted_at.
        Pembiayaan::withTrashed()->whereIn('nokontrak', ['BULK-001', 'BULK-002', 'BULK-003'])->each(
            fn (Pembiayaan $p) => $this->assertNotNull($p->deleted_at)
        );
    }

    /**
     * withTrashed() menampilkan semua data termasuk yang sudah di-softdelete.
     */
    public function test_with_trashed_includes_deleted_records(): void
    {
        Agunan::create(['nokontrak' => 'KTR-A', 'noreg' => 'N1', 'urut' => 1, 'jnsjamin' => 'TANAH', 'nominallikuid' => 0]);
        Agunan::create(['nokontrak' => 'KTR-B', 'noreg' => 'N2', 'urut' => 1, 'jnsjamin' => 'BANGUNAN', 'nominallikuid' => 0]);

        // Hapus satu record.
        Agunan::where('nokontrak', 'KTR-A')->first()->delete();

        $this->assertCount(1, Agunan::all());
        $this->assertCount(2, Agunan::withTrashed()->get());
    }

    /**
     * onlyTrashed() hanya menampilkan record yang di-softdelete.
     */
    public function test_only_trashed_returns_only_deleted_records(): void
    {
        HistoryPembiayaan::create([
            'nokontrak' => 'KTR-X1', 'kdprd' => 'P001', 'kdloc' => 'L001',
            'pokpby' => 'AKD01', 'osmdlc' => 0, 'osmgnc' => 0, 'tgkmdl' => 0,
            'tgkmgn' => 0, 'haritgk' => 0, 'col' => 1, 'stsrec' => 'A', 'ppka' => 0, 'periode' => '202609',
        ]);
        HistoryPembiayaan::create([
            'nokontrak' => 'KTR-X2', 'kdprd' => 'P001', 'kdloc' => 'L001',
            'pokpby' => 'AKD01', 'osmdlc' => 0, 'osmgnc' => 0, 'tgkmdl' => 0,
            'tgkmgn' => 0, 'haritgk' => 0, 'col' => 1, 'stsrec' => 'A', 'ppka' => 0, 'periode' => '202608',
        ]);

        HistoryPembiayaan::where('nokontrak', 'KTR-X1')->first()->delete();

        $trashed = HistoryPembiayaan::onlyTrashed()->get();
        $this->assertCount(1, $trashed);
        $this->assertSame('KTR-X1', $trashed->first()->nokontrak);
    }
}
