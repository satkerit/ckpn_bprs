<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah index pada created_at di tabel upload_batch untuk mengoptimalkan
     * query pagination riwayat upload dan mencegah error 1038 Out of sort memory.
     */
    public function up(): void
    {
        Schema::table('upload_batch', function (Blueprint $table) {
            $table->index('created_at', 'upload_batch_created_at_idx');
        });
    }
};
