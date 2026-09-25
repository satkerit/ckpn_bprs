<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** Daftar permission awal sistem CKPN. */
    private const PERMISSIONS = [
        'dashboard.view',
        'periode.view', 'periode.manage', 'periode.unlock',
        'klasifikasi.view', 'klasifikasi.manage',
        'pd.calculate', 'pd.view',
        'lgd.calculate', 'lgd.view', 'lgd.input',
        'upload.pembiayaan', 'upload.history', 'upload.jaminan', 'upload.kantor', 'upload.produk', 'upload.jaminan_setup',
        'ckpn.calculate', 'ckpn.view', 'ckpn.export',
        'user.manage', 'role.manage', 'permission.manage',
        'setup.manage', 'audit.view',
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        // Administrator: semua akses.
        $admin = Role::query()->firstOrCreate(['name' => 'Administrator']);
        $admin->syncPermissions(self::PERMISSIONS);

        // Manajer Risiko: analisis + perhitungan, tanpa pengelolaan user/role.
        $manajer = Role::query()->firstOrCreate(['name' => 'Manajer Risiko']);
        $manajer->syncPermissions([
            'dashboard.view',
            'periode.view', 'periode.manage',
            'klasifikasi.view', 'klasifikasi.manage',
            'pd.calculate', 'pd.view',
            'lgd.calculate', 'lgd.view', 'lgd.input',
            'ckpn.calculate', 'ckpn.view', 'ckpn.export',
            'upload.pembiayaan', 'upload.history', 'upload.jaminan', 'upload.kantor', 'upload.produk', 'upload.jaminan_setup',
        ]);

        // Analis: hanya baca + input LGD.
        $analis = Role::query()->firstOrCreate(['name' => 'Analis']);
        $analis->syncPermissions([
            'dashboard.view',
            'periode.view',
            'klasifikasi.view',
            'pd.view', 'lgd.view', 'lgd.input',
            'ckpn.view',
        ]);

        // Operator Data: upload saja.
        $operator = Role::query()->firstOrCreate(['name' => 'Operator Data']);
        $operator->syncPermissions([
            'dashboard.view',
            'periode.view',
            'upload.pembiayaan', 'upload.history', 'upload.jaminan', 'upload.kantor', 'upload.produk', 'upload.jaminan_setup',
        ]);
    }
}
