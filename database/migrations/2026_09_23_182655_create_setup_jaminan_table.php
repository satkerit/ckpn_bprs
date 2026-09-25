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
        Schema::create('setup_jaminan', function (Blueprint $table) {
            $table->string('kdjam', 20)->primary();
            $table->string('ket', 150);
            $table->decimal('bobot', 5, 2)->default(100.00); // persentase 0.00 - 100.00
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
};
