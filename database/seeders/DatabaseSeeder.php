<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ReferensiSeeder::class,
            RolePermissionSeeder::class,
            SetupParameterSeeder::class,
            AkadRoleSeeder::class,
            AdminUserSeeder::class,
        ]);

        // User contoh untuk pengujian manual (bukan seed produksi).
        if (app()->environment('local') && ! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
