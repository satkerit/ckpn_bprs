<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Samakan panjang kolom jnsjamin dengan setup_jaminan.kdjam (20).
     */
    public function up(): void
    {
        Schema::table('agunan', function (Blueprint $table): void {
            $table->string('jnsjamin', 20)->change();
        });

        Schema::table('ckpn_lgd_parameter', function (Blueprint $table): void {
            $table->string('jnsjamin', 20)->nullable()->change();
        });

        Schema::table('ckpn_lgd_hasil', function (Blueprint $table): void {
            $table->string('jnsjamin', 20)->nullable()->change();
        });
    }

    /**
     * Kembalikan ke panjang semula (10).
     */
};
