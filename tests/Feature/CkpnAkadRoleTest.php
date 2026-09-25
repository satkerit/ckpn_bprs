<?php

namespace Tests\Feature;

use App\Enums\SyaratMasukCkpn;
use App\Models\AkadRole;
use App\Models\HistoryPembiayaan;
use App\Models\Kantor;
use App\Models\KodeAkad;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Services\Ckpn\EadCalculator;
use App\Services\Ckpn\PdMigrationCalculator;
use App\Services\Ckpn\RollRateCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CkpnAkadRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Kantor::create(['kdloc' => '01', 'nama' => 'Kantor Pusat']);
        ProdukPembiayaan::create(['kdprd' => 'PRD', 'nama' => 'Produk Test', 'pokpby' => '06']);
        KodeAkad::create(['pokpby' => '06', 'nama' => 'Murabahah', 'skema' => 'margin', 'aktif' => true]);
        KodeAkad::create(['pokpby' => '10', 'nama' => 'IMBT', 'skema' => 'sewa', 'aktif' => true]);
    }

    public function test_ead_respects_configured_columns(): void
    {
        AkadRole::query()->create([
            'pokpby' => '06',
            'ead_osmdlc' => true,
            'ead_osmgnc' => false,
            'ead_tgkmdl' => false,
            'ead_tgkmgn' => true,
            'syarat_masuk' => SyaratMasukCkpn::Selalu,
        ]);

        $pembiayaan = Pembiayaan::create(['nokontrak' => 'MUR-002', 'nocif' => 'CIF', 'nama' => 'A', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '06', 'gunadeb' => '1']);
        $history = HistoryPembiayaan::create([
            'nokontrak' => 'MUR-002',
            'kdprd' => 'PRD',
            'kdloc' => '01',
            'pokpby' => '06',
            'periode' => '202502',
            'osmdlc' => 100,
            'tgkmdl' => 25,
            'tgkmgn' => 10,
        ]);

        $this->assertSame(110.0, app(EadCalculator::class)->calculate($pembiayaan, $history));
    }

    public function test_roll_rate_snapshot_excludes_out_of_scope_akad(): void
    {
        // IMBT lancar (belum jatuh tempo) tidak masuk snapshot.
        AkadRole::query()->create([
            'pokpby' => '10',
            'ead_osmdlc' => false,
            'ead_osmgnc' => false,
            'ead_tgkmdl' => true,
            'ead_tgkmgn' => false,
            'syarat_masuk' => SyaratMasukCkpn::JatuhTempo,
        ]);

        $pembiayaan = Pembiayaan::create(['nokontrak' => 'IMBT-001', 'nocif' => 'CIF', 'nama' => 'A', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'gunadeb' => '1']);
        $histories = collect([
            HistoryPembiayaan::make(['nokontrak' => 'IMBT-001', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'periode' => '202501', 'osmdlc' => 100, 'haritgk' => 0, 'stsacc' => 'A']),
            HistoryPembiayaan::make(['nokontrak' => 'IMBT-001', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'periode' => '202502', 'osmdlc' => 100, 'haritgk' => 0, 'stsacc' => 'A']),
        ]);

        $rows = app(RollRateCalculator::class)->calculate($histories, '202502', '202501', 1);

        $this->assertCount(0, $rows);
    }

    public function test_pd_migration_snapshot_follows_akad_role(): void
    {
        // Akad 10: masuk hanya bila ada tunggakan pokok.
        AkadRole::query()->create([
            'pokpby' => '10',
            'ead_osmdlc' => false,
            'ead_osmgnc' => false,
            'ead_tgkmdl' => true,
            'ead_tgkmgn' => false,
            'syarat_masuk' => SyaratMasukCkpn::AdaTunggakan,
        ]);

        Pembiayaan::create(['nokontrak' => 'IN-001', 'nocif' => 'CIF', 'nama' => 'Masuk', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'gunadeb' => '1']);
        Pembiayaan::create(['nokontrak' => 'OUT-001', 'nocif' => 'CIF', 'nama' => 'Keluar', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'gunadeb' => '1']);

        $histories = collect([
            HistoryPembiayaan::make(['nokontrak' => 'IN-001', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'periode' => '202501', 'tgkmdl' => 20, 'haritgk' => 30, 'stsacc' => 'A'])->setRelation('pembiayaan', Pembiayaan::query()->find('IN-001')),
            HistoryPembiayaan::make(['nokontrak' => 'IN-001', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'periode' => '202502', 'tgkmdl' => 20, 'haritgk' => 60, 'stsacc' => 'A'])->setRelation('pembiayaan', Pembiayaan::query()->find('IN-001')),
            HistoryPembiayaan::make(['nokontrak' => 'OUT-001', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'periode' => '202501', 'tgkmdl' => 0, 'haritgk' => 0, 'stsacc' => 'A'])->setRelation('pembiayaan', Pembiayaan::query()->find('OUT-001')),
            HistoryPembiayaan::make(['nokontrak' => 'OUT-001', 'kdprd' => 'PRD', 'kdloc' => '01', 'pokpby' => '10', 'periode' => '202502', 'tgkmdl' => 0, 'haritgk' => 0, 'stsacc' => 'A'])->setRelation('pembiayaan', Pembiayaan::query()->find('OUT-001')),
        ]);

        $rows = app(RollRateCalculator::class)->calculate($histories, '202502', '202501', 1);
        $pd = app(PdMigrationCalculator::class)->calculate($rows);

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows->first()['jumlah_rekening']);
        $this->assertCount(1, $pd);
        $this->assertStringContainsString('pokpby=10', $pd[0]['segment_key']);
    }
}
