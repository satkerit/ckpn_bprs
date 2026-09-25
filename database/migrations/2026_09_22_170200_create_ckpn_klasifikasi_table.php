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
        Schema::create('ckpn_klasifikasi', function (Blueprint $table) {
            $table->id();
            $table->char('periode', 6)->index();
            $table->string('nokontrak', 30)->index();
            $table->string('tipe', 20)->default('kolektif')->index();
            $table->text('alasan')->nullable();
            $table->string('metode_individual', 30)->nullable();
            $table->decimal('nilai_individual', 18, 2)->default(0);
            $table->foreignId('ditentukan_oleh')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['periode', 'nokontrak']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
