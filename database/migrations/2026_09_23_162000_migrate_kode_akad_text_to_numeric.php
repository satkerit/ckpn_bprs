<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pemetaan kode akad lama (teks) ke kode akad numerik yang berlaku.
     * MUDHARABAH, SALAM, dan ISTISHNA tidak punya padanan numerik sehingga
     * dibiarkan apa adanya dan harus ditangani manual bila masih dipakai.
     */
    private const PEMETAAN = [
        'MURABAHAH' => '06',
        'MUSYARAKAH' => '03',
        'MULTIJASA' => '13',
        'IJARAH MULTIJASA' => '13',
        'IJARAH' => '09',
        'IMBT' => '10',
        'IJARAH MUNTAHIYAH BITTAMLIK' => '10',
        'QARDH' => '11',
    ];

    /** Tabel yang menyimpan kode akad langsung pada kolom pokpby. */
    private const TABEL_POKPBY = ['pembiayaan', 'history_pembiayaan', 'ckpn_hasil'];

    public function up(): void
    {
        $this->ubah(self::PEMETAAN);
    }

    /**
     * @param  array<string, string>  $pemetaan
     */
    private function ubah(array $pemetaan): void
    {
        foreach ($pemetaan as $lama => $baru) {
            foreach (self::TABEL_POKPBY as $tabel) {
                DB::table($tabel)->where('pokpby', $lama)->update(['pokpby' => $baru]);
            }
        }
    }
};
