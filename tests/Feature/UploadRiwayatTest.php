<?php

namespace Tests\Feature;

use App\Enums\JenisUpload;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UploadRiwayatTest extends TestCase
{
    use RefreshDatabase;

    public function test_riwayat_shows_failure_reason_and_uploader(): void
    {
        $user = User::factory()->create(['name' => 'Operator Uji']);
        $user->givePermissionTo(Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']));

        UploadBatch::query()->create([
            'jenis' => JenisUpload::Pembiayaan,
            'file_name' => 'template_pembiayaan.xlsx',
            'total_baris' => 3,
            'baris_sukses' => 2,
            'baris_gagal' => 1,
            'status' => 'completed_with_errors',
            'error_log' => [
                ['row' => ['nokontrak' => '', 'nama' => 'Tanpa Kontrak'], 'message' => 'Kolom nokontrak wajib diisi.'],
            ],
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/upload/riwayat')
            ->assertOk()
            ->assertSeeText('Selesai dengan error')
            ->assertSeeText('Lihat alasan 1 baris gagal')
            ->assertSeeText('Kolom nokontrak wajib diisi.')
            ->assertSeeText('Tanpa Kontrak')
            ->assertSeeText('Operator Uji');
    }

    public function test_error_report_lists_failed_rows_with_their_values(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']));

        $batch = UploadBatch::query()->create([
            'jenis' => JenisUpload::Pembiayaan,
            'file_name' => 'template_pembiayaan.xlsx',
            'total_baris' => 2,
            'baris_sukses' => 1,
            'baris_gagal' => 1,
            'status' => 'completed_with_errors',
            'error_log' => [
                ['baris' => 3, 'kolom' => 'nokontrak', 'nilai' => '', 'row' => ['nokontrak' => '', 'nama' => 'Tanpa Kontrak'], 'message' => 'Kolom nokontrak wajib diisi.'],
            ],
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/upload/riwayat')
            ->assertOk()
            ->assertSeeText('Unduh laporan error (.xlsx)');

        $response = $this->actingAs($user)->get('/upload/riwayat/'.$batch->id.'/error');
        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('error_upload_'.$batch->id.'.xlsx', (string) $response->headers->get('content-disposition'));

        $rows = IOFactory::load($response->getFile()->getPathname())->getActiveSheet()->toArray();
        $columns = JenisUpload::Pembiayaan->kolom();

        $this->assertSame(array_merge(['baris', 'kolom_masalah', 'nilai_masalah', 'pesan_kesalahan'], $columns), $rows[0]);
        $this->assertEquals(3, $rows[1][0]);
        $this->assertSame('nokontrak', $rows[1][1]);
        $this->assertEmpty($rows[1][2]);
        $this->assertSame('Kolom nokontrak wajib diisi.', $rows[1][3]);
        $this->assertSame('Tanpa Kontrak', $rows[1][4 + array_search('nama', $columns, true)]);
    }

    public function test_error_report_supports_csv_format(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']));

        $batch = UploadBatch::query()->create([
            'jenis' => JenisUpload::Kantor,
            'file_name' => 'template_kantor.xlsx',
            'status' => 'completed_with_errors',
            'baris_gagal' => 1,
            'error_log' => [
                ['baris' => 2, 'row' => ['kdloc' => '', 'nama' => 'Kantor Tanpa Kode'], 'message' => 'Kolom kdloc wajib diisi.'],
            ],
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/upload/riwayat/'.$batch->id.'/error?format=csv');
        $response->assertOk();
        $this->assertStringContainsString('error_upload_'.$batch->id.'.csv', (string) $response->headers->get('content-disposition'));

        $content = file_get_contents($response->getFile()->getPathname());
        $this->assertStringContainsString('Kantor Tanpa Kode', $content);
        $this->assertStringContainsString('Kolom kdloc wajib diisi.', $content);
    }

    public function test_error_report_is_unavailable_without_failed_rows(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']));

        $batch = UploadBatch::query()->create([
            'jenis' => JenisUpload::Kantor,
            'file_name' => 'template_kantor.xlsx',
            'status' => 'completed',
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/upload/riwayat/'.$batch->id.'/error')
            ->assertNotFound();
    }

    public function test_error_report_requires_dashboard_permission(): void
    {
        $user = User::factory()->create();

        $batch = UploadBatch::query()->create([
            'jenis' => JenisUpload::Kantor,
            'file_name' => 'template_kantor.xlsx',
            'status' => 'completed_with_errors',
            'error_log' => [['message' => 'Kolom wajib tidak ditemukan: kdloc']],
        ]);

        $this->actingAs($user)
            ->get('/upload/riwayat/'.$batch->id.'/error')
            ->assertForbidden();
    }

    public function test_riwayat_shows_file_level_failure_message(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']));

        UploadBatch::query()->create([
            'jenis' => JenisUpload::History,
            'file_name' => 'rusak.xlsx',
            'status' => 'failed',
            'error_log' => [['message' => 'Kolom wajib tidak ditemukan: periode']],
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/upload/riwayat')
            ->assertOk()
            ->assertSeeText('Gagal')
            ->assertSeeText('Kolom wajib tidak ditemukan: periode');
    }
}
