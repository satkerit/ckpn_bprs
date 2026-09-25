<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah soft delete ke ckpn_run agar data perhitungan historis
     * tidak terhapus permanen. Data yang di-delete masih bisa di-restore.
     */
    public function up(): void
    {
        Schema::table('ckpn_run', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
