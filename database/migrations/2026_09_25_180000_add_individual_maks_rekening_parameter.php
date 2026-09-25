<?php

use App\Models\SetupParameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Parameter ambang jumlah rekening NPF yang menjadi CKPN Individual.
     * Nilai awal 10 rekening; dapat diubah lewat menu Setup.
     */
    public function up(): void
    {
        if (SetupParameter::query()->where('kunci', 'ckpn.individual_maks_rekening')->doesntExist()) {
            SetupParameter::set(
                'ckpn.individual_maks_rekening',
                10,
                'ckpn',
                'Jumlah maksimal rekening NPF outstanding terbesar yang diklasifikasikan CKPN Individual',
            );
        }

        // Backfill kolom metode_individual agar klasifikasi otomatis tercatat jelas.
        DB::table('ckpn_klasifikasi')
            ->where('tipe', 'individual')
            ->whereNull('metode_individual')
            ->update(['metode_individual' => 'top_n_npf']);
    }

    public function down(): void
    {
        SetupParameter::query()->where('kunci', 'ckpn.individual_maks_rekening')->delete();
    }
};
