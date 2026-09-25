<?php

namespace Tests\Feature;

use App\Enums\StatusPeriode;
use App\Jobs\ProcessUploadBatch;
use App\Models\CkpnPeriode;
use App\Models\HistoryPembiayaan;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Models\UploadBatch;
use App\Models\User;
use App\Services\Upload\ImportDataService;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UploadPeriodLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Input manual memakai rule exists, sehingga master data referensi perlu ada.
        $this->seed(ReferensiSeeder::class);
    }

    public function test_authenticated_user_with_upload_permission_can_access_upload_route(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']),
            Permission::create(['name' => 'upload.pembiayaan', 'guard_name' => 'web']),
        ]);

        $this->actingAs($user)
            ->get('/data/pembiayaan')
            ->assertOk()
            ->assertSeeText('Upload File')
            ->assertSeeText('Download Template')
            ->assertSeeText('Input Manual');
    }

    public function test_upload_menu_and_input_form_are_reachable(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::create(['name' => 'upload.pembiayaan', 'guard_name' => 'web']),
        ]);

        $this->actingAs($user)
            ->get('/data/pembiayaan?tab=upload')
            ->assertOk()
            ->assertSeeText('Upload File')
            ->assertSeeText('Download Template')
            ->assertSeeText('Input Manual');

        $this->actingAs($user)
            ->get('/data/pembiayaan?tab=manual')
            ->assertOk()
            ->assertSeeText('Input Manual');
    }

    public function test_manual_input_stores_pembiayaan_record(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::create(['name' => 'upload.pembiayaan', 'guard_name' => 'web']),
        ]);

        ProdukPembiayaan::query()->firstOrCreate(['kdprd' => 'P001'], ['nama' => 'Produk Uji', 'pokpby' => '06']);

        $this->actingAs($user)
            ->post('/data/pembiayaan/store', [
                'nokontrak' => 'K-999',
                'nocif' => 'CIF-999',
                'nama' => 'Nasabah Uji',
                'kdprd' => 'P001',
                'kdloc' => '01',
                'pokpby' => '06',
                'gunadeb' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pembiayaan', [
            'nokontrak' => 'K-999',
            'nama' => 'Nasabah Uji',
        ]);
    }

    public function test_upload_history_to_locked_period_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::create(['name' => 'upload.history', 'guard_name' => 'web']),
        ]);
        CkpnPeriode::query()->create([
            'periode' => '202609',
            'status' => StatusPeriode::Locked,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post('/upload', [
                'jenis' => 'history',
                'file' => $this->fakeHistoryFile('202609'),
            ])
            ->assertRedirect();

        $batch = UploadBatch::query()->first();
        $this->assertNotNull($batch);

        (new ProcessUploadBatch($batch->id))->handle(app(ImportDataService::class));

        $batch->refresh();
        $this->assertSame('failed', $batch->status);
        $this->assertStringContainsString('Periode 202609 sudah terkunci atau final.', json_encode($batch->error_log));
        $this->assertDatabaseMissing('history_pembiayaan', [
            'periode' => '202609',
        ]);
    }

    public function test_manual_history_input_to_locked_period_is_rejected(): void
    {
        $user = $this->historyUser();
        $this->lockedPeriode('202609', $user);
        $this->masters();

        $this->actingAs($user)
            ->post('/data/history/store', $this->historyPayload(['periode' => '202609']))
            ->assertUnprocessable()
            ->assertSeeText('Periode 202609 sudah terkunci atau final.');

        $this->assertDatabaseMissing('history_pembiayaan', ['nokontrak' => 'K-1', 'periode' => '202609']);
    }

    public function test_manual_history_update_of_locked_period_is_rejected(): void
    {
        $user = $this->historyUser();
        $this->lockedPeriode('202609', $user);
        $history = $this->historyRecord('202609');

        $this->actingAs($user)
            ->put('/data/history/'.$history->id, $this->historyPayload(['periode' => '202609', 'osmdlc' => 999]))
            ->assertUnprocessable()
            ->assertSeeText('Periode 202609 sudah terkunci atau final.');

        $this->assertSame('100.00', $history->fresh()->osmdlc);
    }

    public function test_manual_history_update_cannot_move_record_out_of_locked_period(): void
    {
        $user = $this->historyUser();
        $this->lockedPeriode('202609', $user);
        $this->lockedPeriode('202608', $user, StatusPeriode::Draft);
        $history = $this->historyRecord('202609');

        $this->actingAs($user)
            ->put('/data/history/'.$history->id, $this->historyPayload(['periode' => '202608', 'osmdlc' => 999]))
            ->assertUnprocessable()
            ->assertSeeText('Periode 202609 sudah terkunci atau final.');

        $history->refresh();
        $this->assertSame('202609', $history->periode);
        $this->assertSame('100.00', $history->osmdlc);
    }

    public function test_manual_history_delete_of_locked_period_is_rejected(): void
    {
        $user = $this->historyUser();
        $this->lockedPeriode('202609', $user);
        $history = $this->historyRecord('202609');

        $this->actingAs($user)
            ->delete('/data/history/'.$history->id)
            ->assertUnprocessable()
            ->assertSeeText('Periode 202609 sudah terkunci atau final.');

        $this->assertDatabaseHas('history_pembiayaan', ['id' => $history->id]);
    }

    public function test_manual_history_update_of_draft_period_is_allowed(): void
    {
        $user = $this->historyUser();
        $this->lockedPeriode('202608', $user, StatusPeriode::Draft);
        $history = $this->historyRecord('202608');

        $this->actingAs($user)
            ->put('/data/history/'.$history->id, $this->historyPayload(['periode' => '202608', 'osmdlc' => 250]))
            ->assertRedirect(route('data.index', ['jenis' => 'history', 'tab' => 'manual']));

        $this->assertSame('250.00', $history->fresh()->osmdlc);
    }

    private function historyUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create(['name' => 'upload.history', 'guard_name' => 'web']));

        return $user;
    }

    private function lockedPeriode(string $periode, User $user, StatusPeriode $status = StatusPeriode::Locked): void
    {
        CkpnPeriode::query()->create([
            'periode' => $periode,
            'status' => $status,
            'created_by' => $user->id,
        ]);
    }

    /** Master data yang direferensikan rule exists pada input manual history. */
    private function masters(): void
    {
        ProdukPembiayaan::query()->firstOrCreate(['kdprd' => 'P001'], ['nama' => 'Produk Uji', 'pokpby' => '06']);
        Pembiayaan::query()->firstOrCreate(['nokontrak' => 'K-1'], [
            'nocif' => 'CIF-1',
            'nama' => 'Nasabah Uji',
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'gunadeb' => '1',
        ]);
    }

    private function historyRecord(string $periode): HistoryPembiayaan
    {
        $this->masters();

        return HistoryPembiayaan::query()->create([
            'nokontrak' => 'K-1',
            'periode' => $periode,
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'osmdlc' => 100,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function historyPayload(array $overrides = []): array
    {
        return array_merge([
            'nokontrak' => 'K-1',
            'periode' => '202608',
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'osmdlc' => 100,
        ], $overrides);
    }

    private function fakeHistoryFile(string $periode): UploadedFile
    {
        Storage::fake('local');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['nokontrak', 'kdprd', 'kdloc', 'pokpby', 'tglexp', 'osmdlc', 'osmgnc', 'tgkmdl', 'tgkmgn', 'haritgk', 'col', 'stsrec', 'stsacc', 'ppka', 'periode'],
            ['K001', 'P001', '01', '06', '2026-09-30', 1000000, 0, 0, 0, 0, 0, 'A', 'A', 0, $periode],
        ], null, 'A1');

        $path = storage_path('framework/testing/history.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'history.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
