<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom kategori produk dihapus karena jenis penggunaan pembiayaan sudah
     * tersedia pada kolom gunadeb di tabel pembiayaan. Sebagai gantinya produk
     * menyimpan kode akad (pokpby) yang mengacu ke tabel kode_akad, sehingga
     * produk dapat dihubungkan dengan pembiayaan memakai kode akad yang sama.
     */
    public function up(): void
    {
        // SQLite menolak drop column selama indeksnya masih ada.
        Schema::table('produk_pembiayaan', function (Blueprint $table) {
            $table->dropIndex(['kategori']);
        });

        Schema::table('produk_pembiayaan', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });

        Schema::table('produk_pembiayaan', function (Blueprint $table) {
            // Nullable di level database agar baris produk lama tidak gagal
            // migrasi; wajib diisi dijaga oleh rule validasi (input & upload).
            $table->string('pokpby', 10)->nullable()->after('nama')->index();
        });
    }
};
