<?php

namespace Tests\Feature;

use App\Models\HistoryPembiayaan;
use App\Models\KodeAkad;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Models\User;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Dropdown kode produk pada form pembiayaan/history membawa kode akad tiap
 * produk sehingga pilihan dapat disaring mengikuti kode akad (lihat
 * resources/js/app.js).
 */
class ProdukAkadCascadeTest extends TestCase
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

    /** HTML dengan spasi berlebih disamakan agar mudah diperiksa. */
    private function html(string $jenis, string $suffix = ''): string
    {
        $response = $this->actingAs($this->user('upload.'.$jenis))
            ->get('/data/'.$jenis.'?tab=manual'.$suffix)
            ->assertOk();

        return (string) preg_replace('/\s+/', ' ', $response->getContent());
    }

    public function test_form_manual_pembiayaan_membawa_peta_akad_produk(): void
    {
        ProdukPembiayaan::query()->create(['kdprd' => 'P900', 'nama' => 'Produk Musyarakah', 'pokpby' => '03', 'aktif' => true]);

        $html = $this->html('pembiayaan');

        $this->assertStringContainsString('data-akad-select', $html);
        $this->assertStringContainsString('data-produk-select', $html);
        $this->assertStringContainsString('value="P900" data-akad="03"', $html);
        $this->assertStringContainsString('P900 — Produk Musyarakah', $html);
    }

    public function test_form_manual_history_membawa_peta_akad_produk(): void
    {
        $html = $this->html('history');

        $this->assertStringContainsString('data-akad-select', $html);
        $this->assertStringContainsString('data-produk-select', $html);
        $this->assertStringContainsString('value="P001" data-akad="06"', $html);
    }

    public function test_form_edit_history_menyorot_produk_dan_akad_terpilih(): void
    {
        $user = $this->user('upload.history');
        $user->givePermissionTo(Permission::create(['name' => 'upload.pembiayaan', 'guard_name' => 'web']));

        Pembiayaan::query()->create([
            'nokontrak' => 'K-901',
            'nocif' => 'CIF-901',
            'nama' => 'Nasabah Uji',
            'kdprd' => 'P002',
            'kdloc' => '01',
            'pokpby' => '03',
            'gunadeb' => '1',
        ]);

        $history = HistoryPembiayaan::query()->create([
            'nokontrak' => 'K-901',
            'periode' => '202609',
            'kdprd' => 'P002',
            'kdloc' => '01',
            'pokpby' => '03',
            'osmdlc' => 100,
        ]);

        $response = $this->actingAs($user)->get('/data/history/'.$history->id.'/edit')->assertOk();
        $html = (string) preg_replace('/\s+/', ' ', $response->getContent());

        $this->assertStringContainsString('data-produk-select', $html);
        $this->assertStringContainsString('value="P002" data-akad="03" selected', $html);
    }

    public function test_kode_akad_nonaktif_tidak_ditawarkan_untuk_data_baru(): void
    {
        // Dinonaktifkan lewat master kode akad.
        KodeAkad::query()->where('pokpby', '06')->update(['aktif' => false]);

        $this->assertStringNotContainsString('06 — Murabahah', $this->html('pembiayaan'));

        // Form edit produk yang masih memakai akad 06 tetap menampilkannya.
        ProdukPembiayaan::query()->updateOrCreate(
            ['kdprd' => 'P950'],
            ['nama' => 'Produk Lama', 'pokpby' => '06', 'aktif' => true],
        );

        $response = $this->actingAs($this->user('upload.produk'))->get('/data/produk/P950/edit')->assertOk();
        $html = (string) preg_replace('/\s+/', ' ', $response->getContent());

        $this->assertStringContainsString('06 — Murabahah', $html);
        $this->assertStringContainsString('value="06" data-akad="" selected>', $html);
    }

    public function test_form_produk_tetap_memakai_input_kode_produk(): void
    {
        // Form master produk tidak boleh memakai dropdown kode produk karena
        // kolomnya adalah kunci produk itu sendiri.
        $html = $this->html('produk');

        $this->assertStringContainsString('data-akad-select', $html);
        $this->assertStringNotContainsString('data-produk-select', $html);
    }
}
