<?php

namespace Tests\Feature;

use App\Models\Agunan;
use App\Models\HistoryPembiayaan;
use App\Models\Kantor;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Models\SetupJaminan;
use App\Services\Ckpn\EadCalculator;
use App\Services\Ckpn\LgdCalculator;
use Database\Seeders\AkadRoleSeeder;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CkpnCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Kode akad wajib ada dulu: ckpn_akad_role ber-FK ke kode_akad.pokpby.
        $this->seed(ReferensiSeeder::class);

        Kantor::query()->updateOrCreate(['kdloc' => '01'], ['nama' => 'Kantor Pusat']);
        ProdukPembiayaan::query()->updateOrCreate(
            ['kdprd' => 'PRD'],
            ['nama' => 'Produk Test', 'pokpby' => '06'],
        );
        SetupJaminan::create(['kdjam' => 'TANAH', 'ket' => 'Tanah Bangunan']);

        // Role default per kode akad sesuai dokumen metodologi.
        $this->seed(AkadRoleSeeder::class);
    }

    public function test_ead_murabahah_uses_principal_and_margin_arrears(): void
    {
        $pembiayaan = Pembiayaan::create(['nokontrak' => 'MUR-001', 'nocif' => 'CIF-001', 'nama' => 'Nasabah', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '06', 'gunadeb' => '1']);
        $history = $this->createHistory($pembiayaan, ['osmdlc' => 100, 'tgkmdl' => 25, 'tgkmgn' => 10]);

        $this->assertSame(110.0, app(EadCalculator::class)->calculate($pembiayaan, $history));
    }

    public function test_ead_multijasa_uses_principal_and_margin_arrears(): void
    {
        $pembiayaan = Pembiayaan::create(['nokontrak' => 'MUL-001', 'nocif' => 'CIF-001', 'nama' => 'Nasabah', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '13', 'gunadeb' => '1']);
        $history = $this->createHistory($pembiayaan, ['osmdlc' => 100, 'tgkmdl' => 25, 'tgkmgn' => 10]);

        $this->assertSame(110.0, app(EadCalculator::class)->calculate($pembiayaan, $history));
    }

    public function test_ead_current_musyarakah_and_imbt_without_due_are_out_of_scope(): void
    {
        foreach (['03', '10'] as $pokpby) {
            $pembiayaan = Pembiayaan::create(['nokontrak' => $pokpby.'-001', 'nocif' => 'CIF-001', 'nama' => 'Nasabah', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => $pokpby, 'gunadeb' => '1']);
            $history = $this->createHistory($pembiayaan, ['osmdlc' => 100, 'haritgk' => 0]);

            $this->assertSame(0.0, app(EadCalculator::class)->calculate($pembiayaan, $history));
        }
    }

    public function test_lgd_uses_history_data_for_the_requested_period(): void
    {
        $pembiayaan = Pembiayaan::create(['nokontrak' => 'LGD-001', 'nocif' => 'CIF-001', 'nama' => 'Nasabah', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '06', 'gunadeb' => '1']);
        $this->createHistory($pembiayaan, ['periode' => '202501', 'osmdlc' => 999, 'haritgk' => 0, 'col' => 1]);
        $this->createHistory($pembiayaan, ['periode' => '202502', 'osmdlc' => 100, 'tgkmgn' => 20, 'haritgk' => 90, 'col' => 3]);
        Agunan::create(['nokontrak' => $pembiayaan->nokontrak, 'noreg' => 'REG-001', 'urut' => 1, 'jnsjamin' => 'TANAH', 'nominallikuid' => 50]);

        $result = app(LgdCalculator::class)->calculate(collect([$pembiayaan->load('histories', 'agunan')]), '202502');

        $this->assertSame(120.0, $result['total_ead_default']);
        $this->assertSame(50.0, $result['total_penerimaan']);
        $this->assertSame(70.0, $result['shortfall']);
        $this->assertSame(1, $result['jumlah_debitur_default']);
    }

    private function createHistory(Pembiayaan $pembiayaan, array $attributes = []): HistoryPembiayaan
    {
        return HistoryPembiayaan::create(array_merge([
            'nokontrak' => $pembiayaan->nokontrak,
            'kdprd' => 'PRD',
            'kdloc' => '01',
            'pokpby' => $pembiayaan->pokpby,
            'periode' => '202502',
        ], $attributes));
    }
}
