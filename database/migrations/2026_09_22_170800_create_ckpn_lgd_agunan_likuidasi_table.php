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
        Schema::create('ckpn_lgd_agunan_likuidasi', function (Blueprint $table) {
            $table->id();
            $table->char('periode', 6)->index();
            $table->string('nokontrak', 30)->index();
            $table->decimal('nilai_eksekusi', 18, 2)->default(0);
            $table->decimal('nilai_estimasi', 18, 2)->default(0);
            $table->string('sumber', 20)->default('estimasi');
            $table->decimal('biaya_terkait', 18, 2)->default(0);
            $table->date('tanggal_eksekusi')->nullable();
            $table->timestamps();

            $table->unique(['periode', 'nokontrak']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
