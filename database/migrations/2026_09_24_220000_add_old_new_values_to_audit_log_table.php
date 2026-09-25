<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom old_values dan new_values ke tabel audit_log untuk
     * mencatat snapshot sebelum dan sesudah perubahan data.
     */
    public function up(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            // Snapshot nilai sebelum perubahan (untuk operasi update/delete)
            $table->json('old_values')->nullable()->after('perubahan');
            // Snapshot nilai sesudah perubahan (untuk operasi create/update)
            $table->json('new_values')->nullable()->after('old_values');
        });
    }
};
