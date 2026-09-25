<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah foreign key constraints yang masih belum ada untuk
     * menjaga integritas referensial antar tabel utama.
     *
     * Catatan: Constraint ini bersifat RESTRICT on delete agar database
     * mencegah penghapusan data master yang masih direferensikan.
     */
    public function up(): void
    {
        // history_pembiayaan.nokontrak -> pembiayaan.nokontrak
        // Cascade: jika pembiayaan dihapus (soft delete), history mengikuti.
        Schema::table('history_pembiayaan', function (Blueprint $table) {
            $table->foreign('nokontrak', 'fk_hp_nokontrak')
                ->references('nokontrak')
                ->on('pembiayaan')
                ->cascadeOnDelete();
        });

        // agunan.nokontrak -> pembiayaan.nokontrak
        Schema::table('agunan', function (Blueprint $table) {
            $table->foreign('nokontrak', 'fk_agunan_nokontrak')
                ->references('nokontrak')
                ->on('pembiayaan')
                ->cascadeOnDelete();
        });

        // agunan.jnsjamin -> setup_jaminan.kdjam
        // Restrict: jenis jaminan tidak boleh dihapus jika masih ada agunan
        Schema::table('agunan', function (Blueprint $table) {
            $table->foreign('jnsjamin', 'fk_agunan_jnsjamin')
                ->references('kdjam')
                ->on('setup_jaminan')
                ->restrictOnDelete();
        });

        // pembiayaan.kdloc -> kantor.kdloc
        // Restrict: kantor tidak boleh dihapus jika masih ada pembiayaan
        Schema::table('pembiayaan', function (Blueprint $table) {
            $table->foreign('kdloc', 'fk_pembiayaan_kdloc')
                ->references('kdloc')
                ->on('kantor')
                ->restrictOnDelete();
        });

        // pembiayaan.kdprd -> produk_pembiayaan.kdprd
        Schema::table('pembiayaan', function (Blueprint $table) {
            $table->foreign('kdprd', 'fk_pembiayaan_kdprd')
                ->references('kdprd')
                ->on('produk_pembiayaan')
                ->restrictOnDelete();
        });
    }
};
