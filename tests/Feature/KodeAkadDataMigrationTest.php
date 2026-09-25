<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Memastikan data lama yang masih memakai kode akad teks dipetakan ke kode
 * numerik, termasuk segment key hasil roll rate.
 */
class KodeAkadDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** Instance migrasi data kode akad (file mengembalikan anonymous class). */
    private function migrasi(): object
    {
        return require database_path('migrations/2026_09_23_162000_migrate_kode_akad_text_to_numeric.php');
    }

    public function test_data_kode_akad_teks_dipetakan_ke_kode_numerik(): void
    {
        $user = User::factory()->create();

        DB::table('pembiayaan')->insert([
            ['nokontrak' => 'K-1', 'nocif' => 'CIF-1', 'nama' => 'Nasabah 1', 'kdprd' => 'P1', 'kdloc' => '01', 'pokpby' => 'MURABAHAH', 'gunadeb' => '1'],
            ['nokontrak' => 'K-2', 'nocif' => 'CIF-2', 'nama' => 'Nasabah 2', 'kdprd' => 'P1', 'kdloc' => '01', 'pokpby' => 'Murabahah', 'gunadeb' => '1'],
            ['nokontrak' => 'K-3', 'nocif' => 'CIF-3', 'nama' => 'Nasabah 3', 'kdprd' => 'P1', 'kdloc' => '01', 'pokpby' => 'MUDHARABAH', 'gunadeb' => '1'],
        ]);

        DB::table('history_pembiayaan')->insert([
            ['nokontrak' => 'K-1', 'periode' => '202609', 'kdprd' => 'P1', 'kdloc' => '01', 'pokpby' => 'IMBT', 'osmdlc' => 100],
            ['nokontrak' => 'K-2', 'periode' => '202609', 'kdprd' => 'P1', 'kdloc' => '01', 'pokpby' => 'MULTIJASA', 'osmdlc' => 100],
        ]);

        $runId = DB::table('ckpn_run')->insertGetId([
            'periode' => '202609',
            'created_by' => $user->id,
        ]);

        DB::table('ckpn_hasil')->insert([
            'ckpn_run_id' => $runId,
            'periode' => '202609',
            'nokontrak' => 'K-1',
            'pokpby' => 'QARDH',
        ]);

        DB::table('ckpn_roll_rate')->insert([
            'ckpn_run_id' => $runId,
            'periode_asal' => '202608',
            'periode_tujuan' => '202609',
            'segment_key' => 'kdloc=01|pokpby=MUSYARAKAH|kdprd=P1',
            'bucket_asal' => '1',
            'bucket_tujuan' => '2',
            'jumlah_rekening' => 1,
        ]);

        $this->migrasi()->up();

        // Kode teks dipetakan, tidak peduli besar-kecil hurufnya.
        $this->assertSame('06', DB::table('pembiayaan')->where('nokontrak', 'K-1')->value('pokpby'));
        $this->assertSame('06', DB::table('pembiayaan')->where('nokontrak', 'K-2')->value('pokpby'));
        $this->assertSame('10', DB::table('history_pembiayaan')->where('nokontrak', 'K-1')->value('pokpby'));
        $this->assertSame('13', DB::table('history_pembiayaan')->where('nokontrak', 'K-2')->value('pokpby'));
        $this->assertSame('11', DB::table('ckpn_hasil')->value('pokpby'));
        $this->assertSame('kdloc=01|pokpby=03|kdprd=P1', DB::table('ckpn_roll_rate')->value('segment_key'));

        // Kode tanpa padanan numerik dibiarkan agar tidak menghilangkan data.
        $this->assertSame('MUDHARABAH', DB::table('pembiayaan')->where('nokontrak', 'K-3')->value('pokpby'));
    }

    public function test_migrasi_dapat_dibatalkan(): void
    {
        DB::table('pembiayaan')->insert([
            ['nokontrak' => 'K-1', 'nocif' => 'CIF-1', 'nama' => 'Nasabah 1', 'kdprd' => 'P1', 'kdloc' => '01', 'pokpby' => 'MURABAHAH', 'gunadeb' => '1'],
        ]);

        $migrasi = $this->migrasi();
        $migrasi->up();
        $this->assertSame('06', DB::table('pembiayaan')->value('pokpby'));

        $migrasi->down();
        $this->assertSame('MURABAHAH', DB::table('pembiayaan')->value('pokpby'));
    }
}
