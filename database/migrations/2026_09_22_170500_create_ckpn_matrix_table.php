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
        Schema::create('ckpn_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->string('metode', 20)->default('netflow');
            $table->string('segment_key', 255)->default('')->index();
            $table->string('bucket_asal', 5);
            $table->string('bucket_tujuan', 5);
            $table->decimal('probabilitas', 9, 6)->default(0);
            $table->unsignedTinyInteger('sumber_n_matrix')->default(0);
            $table->timestamps();

            $table->unique(['ckpn_run_id', 'metode', 'segment_key', 'bucket_asal', 'bucket_tujuan'], 'ckpn_matrix_unique');
            $table->index(['ckpn_run_id', 'segment_key', 'bucket_asal']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
