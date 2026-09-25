<?php

namespace Tests\Feature;

use App\Livewire\Master\KodeAkad as KodeAkadMaster;
use App\Models\KodeAkad;
use App\Models\ProdukPembiayaan;
use App\Models\User;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KodeAkadMasterTest extends TestCase
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

    public function test_halaman_menampilkan_daftar_kode_akad(): void
    {
        $this->actingAs($this->user('setup.manage'))
            ->get('/master/kode-akad')
            ->assertOk()
            ->assertSeeText('Master Kode Akad')
            ->assertSeeText('Murabahah')
            ->assertSeeText('Ijarah Multijasa')
            ->assertSeeText('Ijarah Muntahiyah Bittamlik');
    }

    public function test_halaman_hanya_untuk_pemilik_permission_setup_manage(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/master/kode-akad')
            ->assertForbidden();
    }

    public function test_kode_akad_baru_dapat_ditambahkan(): void
    {
        $user = $this->user('setup.manage');

        Livewire::actingAs($user)
            ->test(KodeAkadMaster::class)
            ->set('pokpby', '07')
            ->set('nama', 'Salam')
            ->set('skema', 'margin')
            ->set('aktif', true)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kode_akad', ['pokpby' => '07', 'nama' => 'Salam', 'skema' => 'margin']);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'kode_akad.created', 'model_id' => '07']);
    }

    public function test_kode_akad_wajib_diisi_dan_tidak_boleh_ganda(): void
    {
        $user = $this->user('setup.manage');

        Livewire::actingAs($user)
            ->test(KodeAkadMaster::class)
            ->set('pokpby', '')
            ->set('nama', '')
            ->call('simpan')
            ->assertHasErrors(['pokpby' => 'required', 'nama' => 'required']);

        Livewire::actingAs($user)
            ->test(KodeAkadMaster::class)
            ->set('pokpby', '06')
            ->set('nama', 'Duplikat')
            ->call('simpan')
            ->assertHasErrors(['pokpby' => 'unique']);
    }

    public function test_skema_harus_sesuai_daftar(): void
    {
        Livewire::actingAs($this->user('setup.manage'))
            ->test(KodeAkadMaster::class)
            ->set('pokpby', '07')
            ->set('nama', 'Salam')
            ->set('skema', 'tidak-ada')
            ->call('simpan')
            ->assertHasErrors(['skema' => 'in']);

        $this->assertDatabaseMissing('kode_akad', ['pokpby' => '07']);
    }

    public function test_kode_akad_dapat_diubah_tanpa_mengubah_kodenya(): void
    {
        $user = $this->user('setup.manage');

        Livewire::actingAs($user)
            ->test(KodeAkadMaster::class)
            ->call('edit', '06')
            ->assertSet('nama', 'Murabahah')
            ->assertSet('skema', 'margin')
            ->set('nama', 'Murabahah Modifikasi')
            ->set('aktif', false)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kode_akad', ['pokpby' => '06', 'nama' => 'Murabahah Modifikasi', 'aktif' => false]);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'kode_akad.updated', 'model_id' => '06']);
    }

    public function test_kode_akad_yang_masih_dipakai_tidak_dapat_dihapus(): void
    {
        $user = $this->user('setup.manage');
        $this->assertTrue(ProdukPembiayaan::query()->where('pokpby', '06')->exists());

        Livewire::actingAs($user)
            ->test(KodeAkadMaster::class)
            ->call('hapus', '06')
            ->assertHasErrors('hapus');

        $this->assertDatabaseHas('kode_akad', ['pokpby' => '06']);
    }

    public function test_kode_akad_tanpa_pemakai_dapat_dihapus(): void
    {
        $user = $this->user('setup.manage');
        KodeAkad::query()->create(['pokpby' => '99', 'nama' => 'Akad Uji', 'skema' => 'margin', 'aktif' => true]);

        Livewire::actingAs($user)
            ->test(KodeAkadMaster::class)
            ->call('hapus', '99')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('kode_akad', ['pokpby' => '99']);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'kode_akad.deleted', 'model_id' => '99']);
    }

    public function test_aksi_ubah_memuat_nilai_akad_terpilih(): void
    {
        $user = $this->user('setup.manage');

        Livewire::actingAs($user)
            ->test(KodeAkadMaster::class)
            ->call('edit', '10')
            ->assertSet('pokpby', '10')
            ->assertSet('nama', 'Ijarah Muntahiyah Bittamlik')
            ->assertSet('skema', 'sewa')
            ->call('batal')
            ->assertSet('editing', null)
            ->assertSet('pokpby', '');
    }
}
