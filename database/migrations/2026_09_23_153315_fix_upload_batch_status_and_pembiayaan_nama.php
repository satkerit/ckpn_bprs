<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // status 'completed_with_errors' = 23 karakter, naikkan batas ke 50
        Schema::table('upload_batch', function (Blueprint $table) {
            $table->string('status', 50)->default('processing')->change();
        });

        // nama bisa kosong di sumber data Excel
        Schema::table('pembiayaan', function (Blueprint $table) {
            $table->string('nama', 100)->nullable()->change();
        });
    }
};
