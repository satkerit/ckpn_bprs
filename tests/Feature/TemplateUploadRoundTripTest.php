<?php

namespace Tests\Feature;

use App\Enums\JenisUpload;
use App\Enums\StatusPeriode;
use App\Exports\TemplateUploadExport;
use App\Models\CkpnPeriode;
use App\Models\ProdukPembiayaan;
use App\Models\SetupJaminan;
use App\Models\UploadBatch;
use App\Models\User;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Memastikan file template hasil export bisa diisi lalu diunggah kembali tanpa
 * masalah (kontrak export <-> import).
 */
class TemplateUploadRoundTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ReferensiSeeder menyediakan master yang dibutuhkan rule exists
        // (kantor, kode akad, jenis penggunaan, produk).
        $this->seed(ReferensiSeeder::class);

        // Produk referensi untuk test import pembiayaan/history.
        ProdukPembiayaan::query()->firstOrCreate(
            ['kdprd' => 'P001'],
            ['nama' => 'Produk Uji', 'pokpby' => '06', 'aktif' => true]
        );

        // Periode draft yang dipakai baris history.
        CkpnPeriode::query()->firstOrCreate(
            ['periode' => '202609'],
            ['status' => StatusPeriode::Draft, 'created_by' => User::factory()->create()->id],
        );

        // Master jenis jaminan yang dirujuk kolom jnsjamin pada upload jaminan.
        SetupJaminan::query()->firstOrCreate(
            ['kdjam' => 'TANAH'],
            ['ket' => 'Tanah dan Bangunan', 'bobot' => 100],
        );
    }

    public function test_template_contains_heading_row_only(): void
    {
        $user = $this->user();

        foreach (JenisUpload::cases() as $jenis) {
            $rows = $this->templateRows($jenis);

            $this->assertSame($jenis->kolom(), $rows[0], "Judul kolom template {$jenis->value} tidak sesuai.");
            $this->assertCount(1, $rows, "Template {$jenis->value} tidak boleh memuat baris data contoh.");
        }

        $this->actingAs($user)->get('/data/pembiayaan/template')->assertOk();
    }

    public function test_filled_template_is_imported_for_every_jenis(): void
    {
        $user = $this->user();
        $cases = [
            JenisUpload::Pembiayaan->value => [
                'row' => ['K-100', 'CIF-100', 'Nasabah Seratus', 'P001', '01', '06', '1', '2026-09-30'],
                'table' => 'pembiayaan',
                'expected' => ['nokontrak' => 'K-100', 'tglwo' => '20260930'],
            ],
            JenisUpload::History->value => [
                'row' => ['K-100', 'P001', '01', '06', '2026-09-30', 1000, 50, 25, 5, 12, 3, 'A', 'A', 10, '202609'],
                'table' => 'history_pembiayaan',
                'expected' => ['nokontrak' => 'K-100', 'periode' => '202609', 'tglexp' => '20260930'],
            ],
            JenisUpload::Jaminan->value => [
                'row' => ['K-100', 'REG-100', 1, '2026-08-31', 'TANAH', 500000],
                'table' => 'agunan',
                'expected' => ['noreg' => 'REG-100', 'tgltaks' => '20260831'],
            ],
            JenisUpload::Kantor->value => [
                'row' => ['77', 'Kantor Uji', 'Jl. Uji Nomor 1'],
                'table' => 'kantor',
                'expected' => ['kdloc' => '77', 'nama' => 'Kantor Uji'],
            ],
            JenisUpload::Produk->value => [
                'row' => ['P777', 'Produk Uji', '06'],
                'table' => 'produk_pembiayaan',
                'expected' => ['kdprd' => 'P777', 'nama' => 'Produk Uji', 'pokpby' => '06'],
            ],
        ];

        foreach ($cases as $jenis => $case) {
            $path = $this->filledTemplate(JenisUpload::from($jenis), [$case['row']]);

            $this->actingAs($user)
                ->post('/upload', ['jenis' => $jenis, 'file' => $this->uploadedFile($path)])
                ->assertRedirect();

            $batch = UploadBatch::query()->latest('id')->firstOrFail();
            $this->assertSame(1, $batch->baris_sukses, "Import {$jenis} gagal: ".json_encode($batch->error_log));
            $this->assertSame(0, $batch->baris_gagal, "Import {$jenis} gagal: ".json_encode($batch->error_log));

            $this->assertDatabaseHas($case['table'], $case['expected']);
        }
    }

    public function test_produk_aktif_column_is_supported_on_import(): void
    {
        $user = $this->user();
        $jenis = JenisUpload::Produk;

        // Produk lama aktif; kolom aktif dikosongkan pada unggahan berikutnya.
        ProdukPembiayaan::query()->create(['kdprd' => 'P500', 'nama' => 'Produk Lama', 'pokpby' => '06', 'aktif' => true]);

        $path = $this->filledTemplate($jenis, [
            ['P500', 'Produk Lama Baru', '06', ''],
            ['P501', 'Produk Nonaktif', '06', 0],
            ['P502', 'Produk Aktif', '06', 'ya'],
        ]);

        $this->actingAs($user)
            ->post('/upload', ['jenis' => $jenis->value, 'file' => $this->uploadedFile($path)])
            ->assertRedirect();

        $batch = UploadBatch::query()->latest('id')->firstOrFail();
        $this->assertSame(3, $batch->baris_sukses, 'Import produk gagal: '.json_encode($batch->error_log));
        $this->assertSame(0, $batch->baris_gagal, 'Import produk gagal: '.json_encode($batch->error_log));

        // Sel kosong membiarkan status lama, 0/ya menetapkan status baru.
        $this->assertDatabaseHas('produk_pembiayaan', ['kdprd' => 'P500', 'nama' => 'Produk Lama Baru', 'aktif' => true]);
        $this->assertDatabaseHas('produk_pembiayaan', ['kdprd' => 'P501', 'aktif' => false]);
        $this->assertDatabaseHas('produk_pembiayaan', ['kdprd' => 'P502', 'aktif' => true]);
    }

    public function test_produk_aktif_column_accepts_invalid_value_as_failed_row(): void
    {
        $user = $this->user();
        $jenis = JenisUpload::Produk;

        $path = $this->filledTemplate($jenis, [
            ['P600', 'Produk Salah Status', '06', 'mungkin'],
        ]);

        $this->actingAs($user)
            ->post('/upload', ['jenis' => $jenis->value, 'file' => $this->uploadedFile($path)])
            ->assertRedirect();

        $batch = UploadBatch::query()->latest('id')->firstOrFail();
        $this->assertSame(0, $batch->baris_sukses);
        $this->assertSame(1, $batch->baris_gagal);
        $this->assertStringContainsString('Status Aktif', json_encode($batch->error_log));
        $this->assertDatabaseMissing('produk_pembiayaan', ['kdprd' => 'P600']);
    }

    public function test_duplicate_nokontrak_in_same_period_is_rejected_as_failed_row(): void
    {
        $user = $this->user();
        $jenis = JenisUpload::History;

        $path = $this->filledTemplate($jenis, [
            ['K-DUP-01', 'P001', '01', '06', '2026-09-30', 1000, 50, 25, 5, 12, 3, 'A', 'A', 10, '202609'],
            ['K-DUP-01', 'P001', '01', '06', '2026-09-30', 2000, 50, 25, 5, 12, 3, 'A', 'A', 10, '202609'],
        ]);

        $this->actingAs($user)
            ->post('/upload', ['jenis' => $jenis->value, 'file' => $this->uploadedFile($path)])
            ->assertRedirect();

        $batch = UploadBatch::query()->latest('id')->firstOrFail();
        $this->assertSame(1, $batch->baris_sukses);
        $this->assertSame(1, $batch->baris_gagal);
        $this->assertStringContainsString('duplikat pada periode 202609', json_encode($batch->error_log));
    }

    public function test_empty_rows_in_template_are_ignored(): void
    {
        $user = $this->user();
        $jenis = JenisUpload::Kantor;

        $path = $this->filledTemplate($jenis, [
            ['88', 'Kantor Delapan', 'Jl. Delapan'],
            ['', '', ''],
            ['', '', ''],
        ]);

        $this->actingAs($user)
            ->post('/upload', ['jenis' => $jenis->value, 'file' => $this->uploadedFile($path)])
            ->assertRedirect();

        $batch = UploadBatch::query()->latest('id')->firstOrFail();
        $this->assertSame(1, $batch->baris_sukses, json_encode($batch->error_log));
        $this->assertSame(0, $batch->baris_gagal, json_encode($batch->error_log));
        $this->assertSame(1, $batch->total_baris);
    }

    public function test_legacy_template_example_row_is_rejected_not_stored(): void
    {
        $user = $this->user();
        $jenis = JenisUpload::Pembiayaan;

        // Baris contoh yang dulu ikut terkirim pada template lama.
        $path = $this->filledTemplate($jenis, [
            array_merge([''], $jenis->kolom()),
        ]);

        $this->actingAs($user)
            ->post('/upload', ['jenis' => $jenis->value, 'file' => $this->uploadedFile($path)])
            ->assertRedirect();

        $batch = UploadBatch::query()->latest('id')->firstOrFail();
        $this->assertSame(0, $batch->baris_sukses);
        $this->assertSame(1, $batch->baris_gagal);
        $this->assertStringContainsString('Kolom No Kontrak wajib diisi', json_encode($batch->error_log));
        $this->assertDatabaseMissing('pembiayaan', ['nokontrak' => '']);
    }

    public function test_upload_with_missing_column_shows_message_instead_of_error_page(): void
    {
        $user = $this->user();
        $jenis = JenisUpload::Pembiayaan;

        $path = $this->templatePath($jenis);
        $sheet = IOFactory::load($path)->getActiveSheet();
        $sheet->setCellValue('A1', 'nokontrak_hilang');
        (new Xlsx($sheet->getParent()))->save($path);

        $this->actingAs($user)
            ->post('/upload', ['jenis' => $jenis->value, 'file' => $this->uploadedFile($path)])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('failed', UploadBatch::query()->latest('id')->firstOrFail()->status);
    }

    public function test_excel_date_serial_is_normalized(): void
    {
        $user = $this->user();
        $jenis = JenisUpload::Pembiayaan;
        $serial = 46234; // serial tanggal Excel
        $expected = ExcelDate::excelToDateTimeObject($serial)->format('Ymd');

        $path = $this->filledTemplate($jenis, [
            ['K-200', 'CIF-200', 'Nasabah Dua Ratus', 'P001', '01', '06', '1', $serial],
        ]);

        $this->actingAs($user)
            ->post('/upload', ['jenis' => $jenis->value, 'file' => $this->uploadedFile($path)])
            ->assertRedirect();

        $this->assertDatabaseHas('pembiayaan', ['nokontrak' => 'K-200', 'tglwo' => $expected]);
    }

    private function user(): User
    {
        $user = User::factory()->create();

        $user->givePermissionTo(Permission::create([
            'name' => 'upload.pembiayaan',
            'guard_name' => 'web',
        ]));

        foreach (JenisUpload::cases() as $jenis) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => 'upload.'.$jenis->value,
                'guard_name' => 'web',
            ]));
        }

        return $user;
    }

    /** Baris-baris pada file template yang dihasilkan TemplateUploadExport. */
    private function templateRows(JenisUpload $jenis): array
    {
        $path = $this->templatePath($jenis);

        return IOFactory::load($path)->getActiveSheet()->toArray();
    }

    /**
     * Tulis file berdasarkan template hasil export, lalu tambahkan baris data.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function filledTemplate(JenisUpload $jenis, array $rows): string
    {
        $path = $this->templatePath($jenis);
        $sheet = IOFactory::load($path)->getActiveSheet();

        foreach ($rows as $offset => $row) {
            // strictNullComparison agar nilai 0 (mis. kolom aktif) ikut tertulis;
            // fromArray() biasa melewati 0 karena perbandingan longgar dengan null.
            $sheet->fromArray($row, null, 'A'.($offset + 2), true);
        }

        (new Xlsx($sheet->getParent()))->save($path);

        return $path;
    }

    private function templatePath(JenisUpload $jenis): string
    {
        $path = storage_path('framework/testing/template_'.$jenis->value.'.xlsx');

        file_put_contents($path, Excel::raw(new TemplateUploadExport($jenis), ExcelWriter::XLSX));

        return $path;
    }

    private function uploadedFile(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            basename($path),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
