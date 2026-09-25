<?php

namespace Tests\Feature;

use App\Enums\StatusPeriode;
use App\Enums\TipeCkpn;
use App\Livewire\Klasifikasi\Index;
use App\Models\CkpnKlasifikasi;
use App\Models\CkpnPeriode;
use App\Models\HistoryPembiayaan;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Models\SetupParameter;
use App\Models\User;
use App\Services\Ckpn\KlasifikasiCkpnService;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KlasifikasiCkpnTest extends TestCase
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
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function history(string $nokontrak, int $col, float $bakiDebet, string $periode = '202609'): HistoryPembiayaan
    {
        ProdukPembiayaan::query()->firstOrCreate(['kdprd' => 'P001'], ['nama' => 'Produk Uji']);

        Pembiayaan::query()->firstOrCreate(['nokontrak' => $nokontrak], [
            'nocif' => 'CIF-1',
            'nama' => 'Nasabah Uji',
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'gunadeb' => '1',
        ]);

        return HistoryPembiayaan::query()->create([
            'nokontrak' => $nokontrak,
            'periode' => $periode,
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'osmdlc' => $bakiDebet,
            'col' => $col,
            'haritgk' => $col >= 3 ? 90 : 0,
            'stsrec' => 'A',
            'stsacc' => 'A',
        ]);
    }

    public function test_hanya_top_n_npf_outstanding_menjadi_individual(): void
    {
        SetupParameter::set('ckpn.individual_maks_rekening', 2, 'ckpn');

        $this->history('NPF-BESAR', 3, 900_000);
        $this->history('NPF-SEDANG', 4, 500_000);
        $this->history('NPF-KECIL', 5, 100_000);
        $this->history('LANCAR', 1, 700_000);

        $hasil = app(KlasifikasiCkpnService::class)->klasifikasikan('202609');

        $this->assertSame(2, $hasil['individual']);
        $this->assertSame(2, $hasil['kolektif']);

        $individual = CkpnKlasifikasi::query()->where('tipe', TipeCkpn::Individual->value)->pluck('nokontrak')->all();
        $this->assertEqualsCanonicalizing(['NPF-BESAR', 'NPF-SEDANG'], $individual);

        // NPF peringkat 3 tetap kolektif walau outstanding di atas rekening lancar.
        $this->assertDatabaseHas('ckpn_klasifikasi', [
            'nokontrak' => 'NPF-KECIL',
            'tipe' => TipeCkpn::Kolektif->value,
        ]);

        // Kolektibilitas 1 tidak pernah individual.
        $this->assertDatabaseHas('ckpn_klasifikasi', [
            'nokontrak' => 'LANCAR',
            'tipe' => TipeCkpn::Kolektif->value,
        ]);
    }

    public function test_nilai_individual_manual_tetap_ada_setelah_hitung_ulang(): void
    {
        SetupParameter::set('ckpn.individual_maks_rekening', 1, 'ckpn');
        $this->history('NPF-1', 3, 900_000);
        $this->history('NPF-2', 3, 400_000);

        $this->actingAs($this->user('klasifikasi.manage'));

        CkpnKlasifikasi::query()->create([
            'periode' => '202609',
            'nokontrak' => 'NPF-2',
            'tipe' => TipeCkpn::Individual,
            'nilai_individual' => 12_500,
        ]);

        app(KlasifikasiCkpnService::class)->klasifikasikan('202609');

        $this->assertSame('12500.00', CkpnKlasifikasi::query()->where('nokontrak', 'NPF-2')->value('nilai_individual'));
    }

    public function test_periode_terkunci_menolak_hitung_ulang(): void
    {
        $user = $this->user('klasifikasi.manage', 'klasifikasi.view');
        CkpnPeriode::query()->create([
            'periode' => '202609',
            'status' => StatusPeriode::Locked,
            'created_by' => $user->id,
        ]);
        $this->history('NPF-1', 3, 900_000);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('periode', '202609')
            ->call('klasifikasikan')
            ->assertStatus(422);

        $this->assertDatabaseCount('ckpn_klasifikasi', 0);
    }

    public function test_halaman_klasifikasi_menolak_tanpa_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/klasifikasi')
            ->assertForbidden();
    }

    public function test_parameter_tidak_menerima_nol(): void
    {
        $this->actingAs($this->user('klasifikasi.manage', 'klasifikasi.view'));

        Livewire::test(Index::class)
            ->set('maksRekening', 0)
            ->call('simpanParameter')
            ->assertHasErrors('maksRekening');
    }
}
