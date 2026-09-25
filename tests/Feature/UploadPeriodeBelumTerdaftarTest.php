<?php

namespace Tests\Feature;

use App\Enums\JenisUpload;
use App\Http\Requests\Data\DataRequest;
use App\Models\CkpnPeriode;
use App\Models\Kantor;
use App\Models\KodeAkad;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadPeriodeBelumTerdaftarTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_row_with_unregistered_periode_is_accepted_by_validation(): void
    {
        // Pastikan periode belum terdaftar di ckpn_periode
        $this->assertFalse(CkpnPeriode::query()->where('periode', '202103')->exists());

        // Master data referensi foreign key
        Kantor::query()->create(['kdloc' => '2', 'nama' => 'Kantor Cabang 2']);
        ProdukPembiayaan::query()->create(['kdprd' => '55', 'nama' => 'Produk 55', 'pokpby' => '06']);
        KodeAkad::query()->create(['pokpby' => '6', 'nama' => 'Murabahah']);
        Pembiayaan::query()->create([
            'nokontrak' => 'K-202103-001',
            'nocif' => 'CIF-001',
            'nama' => 'Nasabah 202103',
            'kdprd' => '55',
            'kdloc' => '2',
            'pokpby' => '6',
            'gunadeb' => '1',
        ]);

        $row = [
            'nokontrak' => 'K-202103-001',
            'periode' => '202103',
            'kdprd' => '55',
            'kdloc' => '2',
            'pokpby' => '6',
            'tglexp' => '2026-07-10',
            'osmdlc' => 60123475,
            'osmgnc' => 22445518.66,
            'tgkmdl' => 1800000,
            'tgkmgn' => 969064.66,
            'haritgk' => 60,
            'col' => 2,
            'stsrec' => 'A',
            'stsacc' => null,
            'ppka' => 0,
        ];

        $validated = DataRequest::validateRow(JenisUpload::History, $row);

        $this->assertSame('202103', $validated['periode']);
        $this->assertSame('K-202103-001', $validated['nokontrak']);
    }
}
