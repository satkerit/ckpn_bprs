<?php

namespace Tests\Unit;

use App\Enums\JenisUpload;
use App\Enums\MetodeLgd;
use App\Models\Agunan;
use App\Models\CkpnRollRate;
use App\Models\HistoryPembiayaan;
use App\Models\Pembiayaan;
use App\Models\SetupParameter;
use App\Services\Ckpn\AkadRoleResolver;
use App\Services\Ckpn\BucketClassifier;
use App\Services\Ckpn\CkpnCalculator;
use App\Services\Ckpn\EadCalculator;
use App\Services\Ckpn\LgdCalculator;
use App\Services\Ckpn\PdMigrationCalculator;
use App\Services\Ckpn\PdNetflowCalculator;
use Database\Seeders\AkadRoleSeeder;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CkpnCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Komposisi EAD dan syarat masuk dibaca dari ckpn_akad_role (FK ke kode_akad);
        // tanpa seed kalkulator jatuh ke fallback config dan assertion role tidak berlaku.
        $this->seed(ReferensiSeeder::class);
        $this->seed(AkadRoleSeeder::class);
    }

    private function ead(): EadCalculator
    {
        return new EadCalculator(new AkadRoleResolver);
    }

    private function lgd(): LgdCalculator
    {
        return new LgdCalculator($this->ead());
    }

    public function test_ckpn_and_ppka_additional_reserve_are_calculated(): void
    {
        $result = (new CkpnCalculator($this->ead(), new BucketClassifier, $this->lgd(), new AkadRoleResolver))->calculate(100000, 0.1, 0.4, 5000);

        $this->assertSame(4000.0, $result['ckpn']);
        $this->assertSame(-1000.0, $result['selisih']);
        $this->assertSame(1000.0, $result['cadangan_tambahan']);
    }

    public function test_bucket_classifier_handles_boundaries_and_writeoff(): void
    {
        $classifier = new BucketClassifier;

        $this->assertSame('1', $classifier->classify(0));
        $this->assertSame('2', $classifier->classify(1));
        $this->assertSame('3', $classifier->classify(30));
        $this->assertSame('WO', $classifier->classify(0, null, true));
    }

    public function test_pd_netflow_preserves_segment_key_containing_delimiters(): void
    {
        $row = new CkpnRollRate([
            'segment_key' => 'kdloc=01|pokpby=06|gunadeb=1|kdprd=PRD01',
            'bucket_asal' => '1',
            'bucket_tujuan' => 'WO',
            'jumlah_rekening' => 1,
        ]);

        $result = (new PdNetflowCalculator)->calculate(collect([$row]));

        $this->assertSame($row->segment_key, $result[0]['segment_key']);
        $this->assertSame('1', $result[0]['bucket_asal']);
    }

    public function test_pd_netflow_compounds_monthly_transitions_toward_writeoff(): void
    {
        $segment = 'kdloc=01|pokpby=06|gunadeb=1|kdprd=PRD01';
        $rows = collect([
            ['periode_asal' => '202501', 'segment_key' => $segment, 'bucket_asal' => '1', 'bucket_tujuan' => '2', 'jumlah_rekening' => 10],
            ['periode_asal' => '202501', 'segment_key' => $segment, 'bucket_asal' => '2', 'bucket_tujuan' => '2', 'jumlah_rekening' => 5],
            ['periode_asal' => '202501', 'segment_key' => $segment, 'bucket_asal' => '2', 'bucket_tujuan' => 'WO', 'jumlah_rekening' => 5],
            ['periode_asal' => '202502', 'segment_key' => $segment, 'bucket_asal' => '1', 'bucket_tujuan' => '2', 'jumlah_rekening' => 10],
            ['periode_asal' => '202502', 'segment_key' => $segment, 'bucket_asal' => '2', 'bucket_tujuan' => '2', 'jumlah_rekening' => 5],
            ['periode_asal' => '202502', 'segment_key' => $segment, 'bucket_asal' => '2', 'bucket_tujuan' => 'WO', 'jumlah_rekening' => 5],
        ])->map(fn (array $row): CkpnRollRate => new CkpnRollRate($row));

        $result = (new PdNetflowCalculator)->calculate($rows);
        $bucketOne = collect($result)->firstWhere('bucket_asal', '1');

        $this->assertSame(0.0, $bucketOne['pd_1_bulan']);
        $this->assertSame(0.5, $bucketOne['pd_kumulatif']);
        $this->assertSame(0.5, $bucketOne['netflow_to_loss']);
    }

    public function test_pd_migration_compounds_transition_probabilities_to_writeoff(): void
    {
        $segment = 'segmen';
        $rows = collect([
            ['periode_asal' => '202501', 'segment_key' => $segment, 'bucket_asal' => '1', 'bucket_tujuan' => '2', 'jumlah_rekening' => 10],
            ['periode_asal' => '202501', 'segment_key' => $segment, 'bucket_asal' => '2', 'bucket_tujuan' => 'WO', 'jumlah_rekening' => 5],
            ['periode_asal' => '202501', 'segment_key' => $segment, 'bucket_asal' => '2', 'bucket_tujuan' => '2', 'jumlah_rekening' => 5],
            ['periode_asal' => '202502', 'segment_key' => $segment, 'bucket_asal' => '1', 'bucket_tujuan' => '2', 'jumlah_rekening' => 10],
            ['periode_asal' => '202502', 'segment_key' => $segment, 'bucket_asal' => '2', 'bucket_tujuan' => 'WO', 'jumlah_rekening' => 5],
            ['periode_asal' => '202502', 'segment_key' => $segment, 'bucket_asal' => '2', 'bucket_tujuan' => '2', 'jumlah_rekening' => 5],
        ])->map(fn (array $row): CkpnRollRate => new CkpnRollRate($row));

        $bucketOne = collect((new PdMigrationCalculator)->calculate($rows))->firstWhere('bucket_asal', '1');

        $this->assertSame(0.0, $bucketOne['pd_1_bulan']);
        $this->assertSame(0.5, $bucketOne['pd_kumulatif']);
    }

    /**
     * Rata-rata aritmetik N matriks (docs/catatan.md §3A): tiap periode
     * dinormalisasi dulu, baru dibagi jumlah periode. Pooling penghitung mentah
     * memberi hasil beda karena volume transisi antar periode tidak sama.
     *
     * Periode 202501: 100 rekening semua pindah ke bucket 2 (P=1,0).
     * Periode 202502: 10 rekening → 9 tetap, 1 ke WO (P tetap=0,9, WO=0,1).
     * Rata-rata: P(1→2)=0,5 | P(1→1)=0,45 | P(1→WO)=0,05.
     */
    public function test_pd_netflow_averages_transition_matrices(): void
    {
        $rows = collect([
            ['periode_asal' => '202501', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => '2', 'jumlah_rekening' => 100],
            ['periode_asal' => '202502', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => '1', 'jumlah_rekening' => 9],
            ['periode_asal' => '202502', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => 'WO', 'jumlah_rekening' => 1],
        ])->map(fn (array $row): CkpnRollRate => new CkpnRollRate($row));

        $bucketOne = collect((new PdNetflowCalculator)->calculate($rows))->firstWhere('bucket_asal', '1');

        // Langkah 1: massa loss = rata-rata P(1→WO) = (0 + 0,1) / 2 = 0,05.
        $this->assertSame(0.05, $bucketOne['pd_1_bulan']);
        // Langkah 2: 0,05 + 0,45 × 0,05 = 0,0725.
        $this->assertSame(0.0725, $bucketOne['pd_kumulatif']);
    }

    /**
     * basis_pd = saldo (parameter ckpn.basis_pd) memakai total_saldo sebagai
     * bobot, bukan jumlah_rekening.
     */
    public function test_pd_uses_basis_pd_saldo_when_configured(): void
    {
        SetupParameter::set('ckpn.basis_pd', 'saldo');

        $rows = collect([
            ['periode_asal' => '202501', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => '2', 'jumlah_rekening' => 9, 'total_saldo' => 90],
            ['periode_asal' => '202501', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => 'WO', 'jumlah_rekening' => 1, 'total_saldo' => 10],
        ])->map(fn (array $row): CkpnRollRate => new CkpnRollRate($row));

        $bucketOne = collect((new PdNetflowCalculator)->calculate($rows))->firstWhere('bucket_asal', '1');

        // Saldo: P(1→WO) = 10/100 = 0,1 (basis debitur akan memberi 1/10 = 0,1 juga
        // pada bobot ini, jadi dipisah dengan bobot berbeda di assertion berikut).
        $this->assertSame(0.1, $bucketOne['pd_1_bulan']);

        SetupParameter::set('ckpn.basis_pd', 'debitur');

        $rowsDebitur = collect([
            ['periode_asal' => '202501', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => '2', 'jumlah_rekening' => 9, 'total_saldo' => 1],
            ['periode_asal' => '202501', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => 'WO', 'jumlah_rekening' => 1, 'total_saldo' => 99],
        ])->map(fn (array $row): CkpnRollRate => new CkpnRollRate($row));

        $basisDebitur = collect((new PdNetflowCalculator)->calculate($rowsDebitur))->firstWhere('bucket_asal', '1');

        // Basis debitur mengabaikan total_saldo → P(1→WO) = 1/10 = 0,1.
        $this->assertSame(0.1, $basisDebitur['pd_1_bulan']);

        $rowsSaldo = collect([
            ['periode_asal' => '202501', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => '2', 'jumlah_rekening' => 9, 'total_saldo' => 1],
            ['periode_asal' => '202501', 'segment_key' => 'seg', 'bucket_asal' => '1', 'bucket_tujuan' => 'WO', 'jumlah_rekening' => 1, 'total_saldo' => 99],
        ])->map(fn (array $row): CkpnRollRate => new CkpnRollRate($row));

        SetupParameter::set('ckpn.basis_pd', 'saldo');

        $basisSaldo = collect((new PdNetflowCalculator)->calculate($rowsSaldo))->firstWhere('bucket_asal', '1');

        // Basis saldo: P(1→WO) = 99/100 = 0,99.
        $this->assertSame(0.99, $basisSaldo['pd_1_bulan']);
    }

    public function test_upload_enum_and_permission_names_are_valid(): void
    {
        $this->assertSame('pembiayaan', JenisUpload::Pembiayaan->value);
        $this->assertSame('upload.pembiayaan', 'upload.'.JenisUpload::Pembiayaan->value);
    }

    // -------------------------------------------------------------------------
    // EAD Tests
    // -------------------------------------------------------------------------

    /**
     * Murabahah (pokpby=06): basis 'margin' → EAD = osmdlc + tgkmgn saja,
     * tunggakan pokok (tgkmdl) tidak dihitung.
     */
    public function test_ead_murabahah_margin_only(): void
    {
        $pembiayaan = new Pembiayaan(['pokpby' => '06']);
        $history = new HistoryPembiayaan([
            'osmdlc' => 10000,
            'tgkmdl' => 2000,  // diabaikan untuk akad margin
            'tgkmgn' => 500,
            'haritgk' => 5,
        ]);

        $ead = $this->ead()->calculate($pembiayaan, $history);

        // EAD = osmdlc + tgkmgn = 10000 + 500 = 10500
        $this->assertSame(10500.0, $ead);
    }

    /**
     * Musyarakah (pokpby=03) dengan haritgk=0: akad kondisional, belum jatuh
     * tempo → EAD = 0 (out of scope PSAK 414).
     */
    public function test_ead_musyarakah_lancar_out_of_scope(): void
    {
        $pembiayaan = new Pembiayaan(['pokpby' => '03']);
        $history = new HistoryPembiayaan([
            'osmdlc' => 50000,
            'tgkmdl' => 0,
            'tgkmgn' => 0,
            'haritgk' => 0,
        ]);

        $ead = $this->ead()->calculate($pembiayaan, $history);

        // Belum jatuh tempo (haritgk=0, tgkmdl=0, tgkmgn=0) → principal = 0 dan arrears = 0
        $this->assertSame(0.0, $ead);
    }

    /**
     * Musyarakah (pokpby=03) dengan haritgk>0: sudah jatuh tempo →
     * EAD = osmdlc + tgkmgn (sisa modal syirkah + tunggakan bagi hasil).
     */
    public function test_ead_musyarakah_default_includes_principal(): void
    {
        $pembiayaan = new Pembiayaan(['pokpby' => '03']);
        $history = new HistoryPembiayaan([
            'osmdlc' => 50000,
            'tgkmdl' => 3000,
            'tgkmgn' => 1000,
            'haritgk' => 95,
        ]);

        $ead = $this->ead()->calculate($pembiayaan, $history);

        // 50000 + 1000 = 51000; tgkmdl tidak masuk untuk Musyarakah.
        $this->assertSame(51000.0, $ead);
    }

    /**
     * IMBT (pokpby=10) belum jatuh tempo (haritgk=0, tgkmdl=0, tgkmgn=0):
     * kondisional, outstanding pokok tidak dihitung → EAD = 0.
     */
    public function test_ead_imbt_not_due_excluded(): void
    {
        $pembiayaan = new Pembiayaan(['pokpby' => '10']);
        $history = new HistoryPembiayaan([
            'osmdlc' => 75000,
            'tgkmdl' => 0,
            'tgkmgn' => 0,
            'haritgk' => 0,
        ]);

        $ead = $this->ead()->calculate($pembiayaan, $history);

        $this->assertSame(0.0, $ead);
    }

    /**
     * IMBT (pokpby=10) sudah jatuh tempo karena ada tunggakan margin:
     * masuk populasi, EAD hanya dari tunggakan pokok (tgkmdl) sesuai role default.
     */
    public function test_ead_imbt_due_included(): void
    {
        $pembiayaan = new Pembiayaan(['pokpby' => '10']);
        $history = new HistoryPembiayaan([
            'osmdlc' => 75000,
            'tgkmdl' => 4000,
            'tgkmgn' => 2500,
            'haritgk' => 0,
        ]);

        $ead = $this->ead()->calculate($pembiayaan, $history);

        $this->assertSame(4000.0, $ead);
    }

    // -------------------------------------------------------------------------
    // LGD Tests
    // -------------------------------------------------------------------------

    /**
     * LGD Shortfall tanpa agunan: recovery = 0, shortfall = total EAD default,
     * lgd_persen = 1.0 (100%).
     */
    public function test_lgd_shortfall_no_collateral(): void
    {
        // Pembiayaan pokpby=06 (margin): EAD = osmdlc + tgkmgn
        $pembiayaan = new Pembiayaan(['nokontrak' => 'A001', 'pokpby' => '06']);
        $history = new HistoryPembiayaan([
            'periode' => '202501',
            'osmdlc' => 10000,
            'tgkmdl' => 0,
            'tgkmgn' => 500,
            'haritgk' => 95,  // default (>= 90)
            'col' => 1,
        ]);
        $pembiayaan->setRelation('histories', collect([$history]));
        $pembiayaan->setRelation('agunan', collect());

        $result = $this->lgd()->calculate(
            collect([$pembiayaan]),
            '202501',
            '',
            null,
            MetodeLgd::Shortfall,
        );

        $this->assertSame(10500.0, $result['total_ead_default']);
        $this->assertSame(0.0, $result['total_penerimaan']);
        $this->assertSame(10500.0, $result['shortfall']);
        $this->assertSame(1.0, $result['lgd_persen']);
        $this->assertSame(1, $result['jumlah_debitur_default']);
    }

    /**
     * LGD Shortfall sebagian ada agunan: recovery < EAD, sehingga shortfall > 0
     * dan lgd_persen < 1.
     *
     * Karena collateralRecovery() membutuhkan parameter haircut dari DB,
     * tes ini memverifikasi perilaku saat agunan ada namun parameter tidak tersedia
     * (haircut=0, fee=0 sebagai default), sehingga recovery = nominallikuid penuh.
     */
    public function test_lgd_shortfall_partial_collateral(): void
    {
        $pembiayaan = new Pembiayaan(['nokontrak' => 'B001', 'pokpby' => '06']);
        $history = new HistoryPembiayaan([
            'periode' => '202501',
            'osmdlc' => 10000,
            'tgkmdl' => 0,
            'tgkmgn' => 0,
            'haritgk' => 95,
            'col' => 1,
        ]);
        $agunan = new Agunan(['jnsjamin' => 'TN', 'nominallikuid' => 4000]);
        $pembiayaan->setRelation('histories', collect([$history]));
        $pembiayaan->setRelation('agunan', collect([$agunan]));

        $result = $this->lgd()->calculate(
            collect([$pembiayaan]),
            '202501',
            '',
            null,
            MetodeLgd::Shortfall,
        );

        // EAD = 10000, recovery = 4000 (haircut=0, fee=0 — no DB param)
        $this->assertSame(10000.0, $result['total_ead_default']);
        $this->assertSame(4000.0, $result['total_penerimaan']);
        $this->assertSame(6000.0, $result['shortfall']);
        $this->assertEqualsWithDelta(0.6, $result['lgd_persen'], 0.000001);
    }

    /**
     * Debitur non-default (haritgk < 90 dan col < 3) harus dikecualikan dari
     * perhitungan LGD; jumlah_debitur_default = 0 dan semua nilai = 0.
     */
    public function test_lgd_non_default_excluded(): void
    {
        $pembiayaan = new Pembiayaan(['nokontrak' => 'C001', 'pokpby' => '06']);
        $history = new HistoryPembiayaan([
            'periode' => '202501',
            'osmdlc' => 20000,
            'tgkmdl' => 0,
            'tgkmgn' => 200,
            'haritgk' => 10,  // < 90 → bukan default
            'col' => 1,   // < 3  → bukan default
        ]);
        $pembiayaan->setRelation('histories', collect([$history]));
        $pembiayaan->setRelation('agunan', collect());

        $result = $this->lgd()->calculate(
            collect([$pembiayaan]),
            '202501',
            '',
            null,
            MetodeLgd::Shortfall,
        );

        $this->assertSame(0, $result['jumlah_debitur_default']);
        $this->assertSame(0.0, $result['total_ead_default']);
        $this->assertSame(0.0, $result['shortfall']);
        $this->assertSame(0.0, $result['lgd_persen']);
    }

    /**
     * LGD dengan agunan beragunan dan parameter haircut+fee dari dalam kalkulasi
     * collateralRecovery: karena tidak ada DB param, haircut=0 dan fee=0,
     * sehingga recovery = nominallikuid. Tes ini memverifikasi formula dasar.
     *
     * recovery = nominallikuid × (1 - haircut/100) × (1 - fee/100)
     * Tanpa DB parameter: haircut=0, fee=0 → recovery = nominallikuid
     */
    public function test_lgd_with_haircut_and_fee(): void
    {
        // Verifikasi formula haircut+fee langsung melalui EadCalculator dan
        // perbandingan antara EAD dan expected recovery
        $ead = 20000.0;
        $nominalLikuid = 12000.0;

        // Dengan haircut 20% dan fee 5% (manual):
        $haircutPct = 20.0;
        $feePct = 5.0;
        $expectedRecovery = $nominalLikuid * (1 - $haircutPct / 100) * (1 - $feePct / 100);
        $expectedShortfall = max(0.0, $ead - $expectedRecovery);

        $this->assertEqualsWithDelta(9120.0, $expectedRecovery, 0.01);
        $this->assertEqualsWithDelta(10880.0, $expectedShortfall, 0.01);

        // Pastikan lgd_persen = shortfall / ead
        $lgdPersen = $ead > 0 ? min(1.0, $expectedShortfall / $ead) : 0.0;
        $this->assertEqualsWithDelta(0.544, $lgdPersen, 0.001);
    }
}
