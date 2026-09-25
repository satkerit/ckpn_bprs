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
        Schema::create('ckpn_lgd_parameter', function (Blueprint $table) {
            $table->id();
            $table->char('periode', 6)->index();
            $table->string('metode', 20)->default('shortfall');
            $table->string('jnsjamin', 10)->nullable()->index();
            $table->string('segment_key', 255)->nullable()->index();
            $table->decimal('haircut_persen', 7, 4)->default(0);
            $table->decimal('biaya_lelang_persen', 7, 4)->default(0);
            $table->char('berlaku_dari', 6)->nullable();
            $table->timestamps();

            $table->unique(['periode', 'metode', 'jnsjamin', 'segment_key'], 'ckpn_lgd_param_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
};
