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
        Schema::create('kode_akad', function (Blueprint $table) {
            $table->string('pokpby', 10)->primary();
            $table->string('nama', 100);
            $table->enum('skema', ['margin', 'ujrah', 'bagihasil', 'sewa'])->default('margin');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
};
