<?php

namespace Tests\Feature;

use App\Enums\StatusRun;
use App\Models\CkpnHasil;
use App\Models\CkpnRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_dashboard_permission_sees_latest_completed_run_metrics(): void
    {
        $permission = Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        $run = CkpnRun::query()->create([
            'periode' => '202609',
            'lookback_bulan' => 12,
            'metode' => 'netflow',
            'metode_lgd' => 'shortfall',
            'status' => StatusRun::Done,
            'total_ead' => 123456.78,
            'total_ckpn' => 23456.78,
            'total_ppka' => 3456.78,
            'cadangan_ppka_tambahan' => 456.78,
            'created_by' => $user->id,
        ]);
        CkpnHasil::query()->create([
            'ckpn_run_id' => $run->id,
            'periode' => '202609',
            'nokontrak' => 'KONTRAK-1',
            'segment_key' => 'segmen',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('202609')
            ->assertSee('123.456,78')
            ->assertSee('23.456,78')
            ->assertSee('3.456,78')
            ->assertSee('456,78')
            ->assertSee('1');
    }

    public function test_user_without_dashboard_permission_cannot_open_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertForbidden();
    }
}
