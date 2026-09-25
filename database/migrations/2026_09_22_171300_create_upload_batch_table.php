<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('upload_batch', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20)->index();
            $table->char('periode', 6)->nullable();
            $table->string('file_name', 255);
            $table->unsignedInteger('total_baris')->default(0);
            $table->unsignedInteger('baris_sukses')->default(0);
            $table->unsignedInteger('baris_gagal')->default(0);
            $table->string('status', 20)->default('processing');
            $table->json('error_log')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
};
