<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah index yang hilang untuk query paling sering digunakan dalam
     * kalkulasi CKPN, export, dan laporan audit.
     */
    public function up(): void
    {
        // history_pembiayaan: query periode+haritgk dan periode+stsacc sering
        // dipakai oleh BucketClassifier dan isDefault() pada kalkulasi LGD/PD.
        Schema::table('history_pembiayaan', function (Blueprint $table) {
            $table->index(['periode', 'haritgk'], 'hp_periode_haritgk_idx');
            $table->index(['periode', 'stsacc'], 'hp_periode_stsacc_idx');
        });

        // ckpn_hasil: query ringkasan per run+segment dan run+bucket
        // dipakai oleh ringkasKantor() dan tampilan halaman CKPN.
        Schema::table('ckpn_hasil', function (Blueprint $table) {
            $table->index(['ckpn_run_id', 'segment_key'], 'ch_run_segment_idx');
            $table->index(['ckpn_run_id', 'bucket'], 'ch_run_bucket_idx');
        });

        // ckpn_roll_rate: full composite index untuk lookup transisi Markov
        // dipakai oleh PdNetflowCalculator dan PdMigrationCalculator.
        Schema::table('ckpn_roll_rate', function (Blueprint $table) {
            $table->index(
                ['ckpn_run_id', 'segment_key', 'bucket_asal', 'bucket_tujuan'],
                'crr_full_idx'
            );
        });

        // audit_log: index aksi+created_at untuk filter log per jenis aksi
        Schema::table('audit_log', function (Blueprint $table) {
            $table->index(['aksi', 'created_at'], 'al_aksi_created_idx');
        });
    }
};
