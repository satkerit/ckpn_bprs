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
        Schema::create('ckpn_ringkasan_kantor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->char('periode', 6)->index();
            $table->string('kdloc', 5)->index();
            $table->unsignedInteger('jumlah_rekening')->default(0);
            $table->decimal('total_ead', 20, 2)->default(0);
            $table->decimal('total_ckpn', 20, 2)->default(0);
            $table->decimal('total_ppka', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(['ckpn_run_id', 'periode', 'kdloc']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
