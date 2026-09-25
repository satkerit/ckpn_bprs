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
        Schema::create('ckpn_lgd_hasil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->string('metode', 20)->default('shortfall');
            $table->string('segment_key', 255)->default('')->index();
            $table->string('jnsjamin', 10)->nullable();
            $table->decimal('total_ead_default', 20, 2)->default(0);
            $table->decimal('total_penerimaan', 20, 2)->default(0);
            $table->decimal('shortfall', 20, 2)->default(0);
            $table->decimal('lgd_persen', 9, 6)->default(0);
            $table->unsignedInteger('jumlah_debitur_default')->default(0);
            $table->timestamps();

            $table->unique(['ckpn_run_id', 'metode', 'segment_key', 'jnsjamin'], 'ckpn_lgd_hasil_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
};
