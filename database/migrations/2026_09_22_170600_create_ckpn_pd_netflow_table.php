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
        Schema::create('ckpn_pd_netflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->string('segment_key', 255)->default('')->index();
            $table->string('bucket_asal', 5);
            $table->decimal('pd_1_bulan', 9, 6)->default(0);
            $table->decimal('pd_kumulatif', 9, 6)->default(0);
            $table->decimal('netflow_to_loss', 9, 6)->default(0);
            $table->timestamps();

            $table->unique(['ckpn_run_id', 'segment_key', 'bucket_asal'], 'ckpn_pd_netflow_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
};
