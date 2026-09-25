<?php

namespace Tests\Feature;

use App\Enums\StatusRun;
use App\Exports\CkpnHasilExport;
use App\Models\CkpnHasil;
use App\Models\CkpnRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExportDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::create(['name' => $permission, 'guard_name' => 'web']));
        }

        return $user;
    }

    public function test_template_upload_can_be_downloaded(): void
    {
        $this->actingAs($this->user('upload.pembiayaan'))
            ->get('/data/pembiayaan/template')
            ->assertOk()
            ->assertDownload('template_pembiayaan.xlsx');
    }

    public function test_ckpn_hasil_can_be_exported(): void
    {
        $this->actingAs($this->user('ckpn.export'))
            ->get('/ckpn/export')
            ->assertOk()
            ->assertDownload('hasil_ckpn.xlsx');
    }

    public function test_ckpn_hasil_export_rows_align_with_headings(): void
    {
        $user = $this->user('ckpn.export');
        $run = CkpnRun::query()->create([
            'periode' => '202609',
            'lookback_bulan' => 12,
            'metode' => 'netflow',
            'metode_lgd' => 'shortfall',
            'status' => StatusRun::Done,
            'created_by' => $user->id,
        ]);
        CkpnHasil::query()->create([
            'ckpn_run_id' => $run->id,
            'periode' => '202609',
            'nokontrak' => 'KONTRAK-1',
            'nama' => 'Nasabah Uji',
            'segment_key' => 'segmen',
            'ead' => 1000,
            'ckpn_psak414' => 50,
        ]);

        $path = storage_path('framework/testing/hasil_ckpn.xlsx');
        file_put_contents($path, Excel::raw(new CkpnHasilExport($run->id), ExcelWriter::XLSX));
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();

        $headings = $rows[0];
        $data = $rows[1];
        $value = fn (string $column) => $data[array_search($column, $headings, true)];

        $this->assertSame(19, count($headings));
        $this->assertSame(19, count($data));
        $this->assertSame('202609', $value('periode'));
        $this->assertSame('KONTRAK-1', $value('nokontrak'));
        $this->assertSame('Nasabah Uji', $value('nama'));
        $this->assertSame(1000, (int) $value('ead'));
        $this->assertSame(50, (int) $value('ckpn_psak414'));
    }
}
