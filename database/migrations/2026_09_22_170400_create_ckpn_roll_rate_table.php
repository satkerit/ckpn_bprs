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
        Schema::create('ckpn_roll_rate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->char('periode_asal', 6);
            $table->char('periode_tujuan', 6);
            $table->string('segment_key', 255)->default('')->index();
            $table->string('bucket_asal', 5);
            $table->string('bucket_tujuan', 5);
            $table->unsignedInteger('jumlah_rekening')->default(0);
            $table->decimal('total_saldo', 20, 2)->default(0);
            $table->timestamps();

            $table->index(['ckpn_run_id', 'periode_asal', 'periode_tujuan']);
            $table->index(['ckpn_run_id', 'segment_key', 'bucket_asal']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
