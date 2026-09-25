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
        Schema::create('cadangan_ppka_tambahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->char('periode', 6)->index();
            $table->decimal('total_ckpn', 20, 2)->default(0);
            $table->decimal('total_ppka', 20, 2)->default(0);
            $table->decimal('nilai_cadangan', 20, 2)->default(0);
            $table->timestamp('dibentuk_pada')->nullable();
            $table->text('keterangan')->nullable();
            $table->unique(['ckpn_run_id', 'periode']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
};
