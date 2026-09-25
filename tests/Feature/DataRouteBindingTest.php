<?php

namespace Tests\Feature;

use App\Models\CkpnPeriode;
use App\Models\HistoryPembiayaan;
use App\Models\Kantor;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Models\User;
use Database\Seeders\ReferensiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataRouteBindingTest extends TestCase
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

    public function test_history_routes_resolve_jenis_enum(): void
    {
        $user = $this->user('upload.history');
        ProdukPembiayaan::query()->firstOrCreate(['kdprd' => 'P001'], ['nama' => 'Produk Uji', 'pokpby' => '06']);
        Pembiayaan::query()->create([
            'nokontrak' => 'K-1',
            'nocif' => 'CIF-1',
            'nama' => 'Nasabah Uji',
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'gunadeb' => '1',
        ]);
        CkpnPeriode::query()->create(['periode' => '202609', 'created_by' => $user->id]);

        $item = HistoryPembiayaan::query()->create([
            'nokontrak' => 'K-1',
            'periode' => '202609',
            'kdprd' => 'P001',
            'kdloc' => '01',
            'pokpby' => '06',
            'osmdlc' => 100,
            'tglexp' => '20260930',
        ]);

        $this->actingAs($user)->get('/data/history')->assertOk();
        $this->actingAs($user)->get('/data/history/'.$item->id)->assertOk();
        $this->actingAs($user)->get('/data/history/'.$item->id.'/edit')->assertOk();

        $this->actingAs($user)
            ->put('/data/history/'.$item->id, [
                'nokontrak' => 'K-1',
                'periode' => '202609',
                'kdprd' => 'P001',
                'kdloc' => '01',
                'pokpby' => '06',
                'osmdlc' => 200,
            ])
            ->assertRedirect(route('data.index', ['jenis' => 'history', 'tab' => 'manual']));

        $this->actingAs($user)
            ->delete('/data/history/'.$item->id)
            ->assertRedirect(route('data.index', ['jenis' => 'history', 'tab' => 'manual']));
    }

    public function test_unknown_jenis_segment_returns_not_found(): void
    {
        $user = $this->user('upload.pembiayaan');

        $this->actingAs($user)->get('/data/tidak-ada')->assertNotFound();
        $this->actingAs($user)->get('/data/tidak-ada/1/edit')->assertNotFound();
    }

    public function test_kantor_routes_resolve_jenis_enum(): void
    {
        $user = $this->user('upload.kantor');

        Kantor::query()->create(['kdloc' => '99', 'nama' => 'Kantor Uji']);

        $this->actingAs($user)->get('/data/kantor')->assertOk();
        $this->actingAs($user)->get('/data/kantor/99')->assertOk();
        $this->actingAs($user)->get('/data/kantor/99/edit')->assertOk();
    }
}
