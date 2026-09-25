<?php

namespace Tests\Feature;

use App\Models\CkpnPeriode;
use App\Models\Kantor;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Models\SetupJaminan;
use App\Models\User;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferensiSeeder::class);
    }

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::create(['name' => $permission, 'guard_name' => 'web']));
        }

        return $user;
    }

    /** Membuat master yang direferensikan rule exists. */
    private function pembiayaan(string $nokontrak = 'K-1'): Pembiayaan
    {
        ProdukPembiayaan::query()->firstOrCreate(['kdprd' => 'P001'], ['nama' => 'Produk Uji']);

        return Pembiayaan::query()->firstOrCreate(['nokontrak' => $nokontrak], [
            'nocif' => 'CIF-1',
            'nama' => 'Nasabah Uji',
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'gunadeb' => '1',
        ]);
    }

    private function periode(string $periode = '202609'): void
    {
        CkpnPeriode::query()->create(['periode' => $periode, 'created_by' => User::factory()->create()->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function historyPayload(array $overrides = []): array
    {
        return array_merge([
            'nokontrak' => 'K-1',
            'periode' => '202609',
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'osmdlc' => 100,
        ], $overrides);
    }

    public function test_manual_input_requires_existing_reference_data(): void
    {
        $user = $this->user('upload.pembiayaan');

        $this->actingAs($user)
            ->post('/data/pembiayaan/store', [
                'nokontrak' => 'K-9',
                'nocif' => 'CIF-9',
                'nama' => 'Nasabah Salah',
                'kdprd' => 'TIDAK-ADA',
                'kdloc' => 'ZZ',
                'pokpby' => 'TIDAKADA',
                'gunadeb' => '9',
            ])
            ->assertInvalid(['kdprd', 'kdloc', 'pokpby', 'gunadeb']);

        $this->assertDatabaseMissing('pembiayaan', ['nokontrak' => 'K-9']);
    }

    public function test_manual_history_accepts_unregistered_period_and_contract(): void
    {
        $user = $this->user('upload.history');

        // Nomor kontrak dan periode tidak wajib terdaftar di master: keterbatasan
        // data master pembiayaan membuat keduanya bisa belum ada padanannya.
        $this->actingAs($user)
            ->post('/data/history/store', $this->historyPayload([
                'nokontrak' => 'K-TIDAK-ADA',
                'periode' => '202612',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('history_pembiayaan', [
            'nokontrak' => 'K-TIDAK-ADA',
            'periode' => '202612',
        ]);
    }

    public function test_rules_match_database_column_lengths(): void
    {
        $user = $this->user('upload.pembiayaan');
        $this->pembiayaan();

        $this->actingAs($user)
            ->post('/data/pembiayaan/store', [
                'nokontrak' => 'K-9',
                'nocif' => 'CIF-9',
                'nama' => 'Nasabah Uji',
                'kdprd' => 'P001',
                'kdloc' => '01',
                'pokpby' => '06060606060', // 11 karakter, kolom varchar(10)
                'gunadeb' => '11', // kolom char(1)
                'tglwo' => '2026-09-30',
            ])
            ->assertInvalid(['pokpby', 'gunadeb']);

        $this->assertDatabaseMissing('pembiayaan', ['nokontrak' => 'K-9']);
    }

    public function test_date_inputs_are_stored_as_ymd(): void
    {
        $user = $this->user('upload.pembiayaan', 'upload.history');
        $this->pembiayaan();
        $this->periode();

        $this->actingAs($user)
            ->post('/data/pembiayaan/store', [
                'nokontrak' => 'K-2',
                'nocif' => 'CIF-2',
                'nama' => 'Nasabah Uji',
                'kdprd' => 'P001',
                'kdloc' => '01',
                'pokpby' => '06',
                'gunadeb' => '1',
                'tglwo' => '2026-09-30',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pembiayaan', ['nokontrak' => 'K-2', 'tglwo' => '20260930']);

        $this->actingAs($user)
            ->post('/data/history/store', $this->historyPayload(['tglexp' => '2026-09-30']))
            ->assertRedirect();

        $this->assertDatabaseHas('history_pembiayaan', ['nokontrak' => 'K-1', 'tglexp' => '20260930']);
    }

    public function test_blank_optional_columns_use_database_defaults(): void
    {
        $user = $this->user('upload.history');
        $this->pembiayaan();
        $this->periode();

        $this->actingAs($user)
            ->post('/data/history/store', $this->historyPayload([
                'osmgnc' => '',
                'tgkmdl' => '',
                'tgkmgn' => '',
                'haritgk' => '',
                'col' => '',
                'stsrec' => '',
                'ppka' => '',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('history_pembiayaan', [
            'nokontrak' => 'K-1',
            'osmgnc' => 0,
            'tgkmdl' => 0,
            'tgkmgn' => 0,
            'haritgk' => 0,
            'col' => 1,
            'stsrec' => 'A',
            'ppka' => 0,
        ]);
    }

    public function test_kolektibilitas_is_limited_to_one_through_five(): void
    {
        $user = $this->user('upload.history');
        $this->pembiayaan();
        $this->periode();

        $this->actingAs($user)
            ->post('/data/history/store', $this->historyPayload(['col' => 9]))
            ->assertInvalid('col');

        $this->actingAs($user)
            ->post('/data/history/store', $this->historyPayload(['col' => 5]))
            ->assertRedirect();
    }

    public function test_update_rejects_duplicate_kantor_code(): void
    {
        $user = $this->user('upload.kantor');
        Kantor::query()->create(['kdloc' => '88', 'nama' => 'Kantor Lain']);

        $this->actingAs($user)
            ->put('/data/kantor/01', ['kdloc' => '88', 'nama' => 'Kantor Pusat'])
            ->assertInvalid('kdloc');

        $this->assertDatabaseHas('kantor', ['kdloc' => '01', 'nama' => 'Kantor Pusat']);
    }

    public function test_update_rejects_duplicate_history_key(): void
    {
        $user = $this->user('upload.history');
        $this->pembiayaan('K-1');
        $this->pembiayaan('K-2');
        $this->periode();

        DB::table('history_pembiayaan')->insert([
            ['nokontrak' => 'K-1', 'periode' => '202609', 'kdprd' => 'P001', 'kdloc' => '01', 'pokpby' => '06', 'osmdlc' => 100],
            ['nokontrak' => 'K-2', 'periode' => '202609', 'kdprd' => 'P001', 'kdloc' => '01', 'pokpby' => '06', 'osmdlc' => 100],
        ]);

        $first = DB::table('history_pembiayaan')->where('nokontrak', 'K-1')->value('id');

        $this->actingAs($user)
            ->put('/data/history/'.$first, $this->historyPayload(['nokontrak' => 'K-2']))
            ->assertInvalid('nokontrak');
    }

    public function test_history_update_keeps_own_key_usable(): void
    {
        $user = $this->user('upload.history');
        $this->pembiayaan();
        $this->periode();

        $this->actingAs($user)->post('/data/history/store', $this->historyPayload())->assertRedirect();

        $id = DB::table('history_pembiayaan')->value('id');

        $this->actingAs($user)
            ->put('/data/history/'.$id, $this->historyPayload(['osmdlc' => 250]))
            ->assertRedirect(route('data.index', ['jenis' => 'history', 'tab' => 'manual']));

        $this->assertDatabaseHas('history_pembiayaan', ['id' => $id, 'osmdlc' => 250]);
    }

    public function test_edit_form_shows_dates_in_html_input_format(): void
    {
        $user = $this->user('upload.history');
        $this->pembiayaan();
        $this->periode();

        $history = DB::table('history_pembiayaan')->insertGetId([
            'nokontrak' => 'K-1',
            'periode' => '202609',
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'osmdlc' => 100,
            'tglexp' => '20260930',
        ]);

        $this->actingAs($user)
            ->get('/data/history/'.$history.'/edit')
            ->assertOk()
            ->assertSee('value="2026-09-30"', false);
    }

    public function test_produk_active_flag_follows_checkbox(): void
    {
        $user = $this->user('upload.produk');

        $this->actingAs($user)
            ->post('/data/produk/store', ['kdprd' => 'P900', 'nama' => 'Produk Baru', 'pokpby' => '06', 'aktif' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('produk_pembiayaan', ['kdprd' => 'P900', 'aktif' => true]);

        $this->actingAs($user)
            ->put('/data/produk/P900', ['kdprd' => 'P900', 'nama' => 'Produk Baru', 'pokpby' => '06'])
            ->assertRedirect();

        $this->assertDatabaseHas('produk_pembiayaan', ['kdprd' => 'P900', 'aktif' => false]);
    }

    public function test_produk_requires_a_registered_kode_akad(): void
    {
        $user = $this->user('upload.produk');

        // Kode akad wajib diisi.
        $this->actingAs($user)
            ->post('/data/produk/store', ['kdprd' => 'P901', 'nama' => 'Produk Tanpa Akad'])
            ->assertInvalid('pokpby');

        // Kode akad harus terdaftar di tabel kode_akad.
        $this->actingAs($user)
            ->post('/data/produk/store', ['kdprd' => 'P901', 'nama' => 'Produk Akad Salah', 'pokpby' => '99'])
            ->assertInvalid('pokpby');

        $this->assertDatabaseMissing('produk_pembiayaan', ['kdprd' => 'P901']);
    }

    public function test_truncate_clears_pembiayaan_and_agunan_but_keeps_history(): void
    {
        $user = $this->user('upload.pembiayaan');
        $this->pembiayaan();
        SetupJaminan::query()->firstOrCreate(['kdjam' => 'TANAH'], ['ket' => 'Tanah Bangunan']);

        DB::table('history_pembiayaan')->insert([
            'nokontrak' => 'K-1', 'periode' => '202609', 'kdprd' => 'P001', 'kdloc' => '01', 'pokpby' => '06', 'osmdlc' => 100,
        ]);
        DB::table('agunan')->insert([
            'nokontrak' => 'K-1', 'noreg' => 'REG-1', 'urut' => 1, 'jnsjamin' => 'TANAH', 'nominallikuid' => 50,
        ]);

        $this->actingAs($user)
            ->post('/data/pembiayaan/truncate')
            ->assertRedirect();

        $this->assertSame(0, DB::table('pembiayaan')->count());
        $this->assertSame(0, DB::table('agunan')->count());
        // History pembiayaan dikecualikan dari truncate.
        $this->assertSame(1, DB::table('history_pembiayaan')->count());
    }

    public function test_truncate_rejects_other_data_types(): void
    {
        $user = $this->user('upload.history', 'upload.pembiayaan');

        $this->actingAs($user)
            ->post('/data/history/truncate')
            ->assertNotFound();
    }

    public function test_truncate_requires_permission(): void
    {
        $this->pembiayaan();

        $this->actingAs($this->user())
            ->post('/data/pembiayaan/truncate')
            ->assertForbidden();

        $this->assertDatabaseHas('pembiayaan', ['nokontrak' => 'K-1']);
    }

    public function test_produk_kode_akad_relates_to_pembiayaan(): void
    {
        $user = $this->user('upload.produk');

        $this->actingAs($user)
            ->post('/data/produk/store', ['kdprd' => 'P902', 'nama' => 'Produk Murabahah', 'pokpby' => '06', 'aktif' => '1'])
            ->assertRedirect();

        $produk = ProdukPembiayaan::query()->where('kdprd', 'P902')->firstOrFail();

        $this->assertSame('Murabahah', $produk->akad?->nama);

        // Pembiayaan dengan kode akad yang sama menunjuk master akad yang sama.
        $pembiayaan = Pembiayaan::query()->create([
            'nokontrak' => 'K-902',
            'nocif' => 'CIF-902',
            'nama' => 'Nasabah Akad',
            'kdprd' => 'P902',
            'kdloc' => '01',
            'pokpby' => '06',
            'gunadeb' => '1',
        ]);

        $this->assertSame($produk->akad?->pokpby, $pembiayaan->akad?->pokpby);
        $this->assertSame('06', $produk->pokpby);

        // Halaman manual dan edit menampilkan pilihan kode akad dari master.
        $this->actingAs($user)
            ->get('/data/produk?tab=manual')
            ->assertOk()
            ->assertSeeText('Kode Akad')
            ->assertSeeText('06 — Murabahah');

        $halaman = $this->actingAs($user)->get('/data/produk/P902/edit');
        $halaman->assertOk()->assertSeeText('06 — Murabahah');

        // Opsi 06 tampil terpilih pada form edit (spasi/newline disamakan).
        $this->assertStringContainsString(
            'value="06" data-akad="" selected>',
            (string) preg_replace('/\s+/', ' ', $halaman->getContent()),
        );
    }
}
