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
        Schema::create('ckpn_bucket_snapshot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->char('periode', 6)->index();
            $table->string('nokontrak', 30)->index();
            $table->string('bucket', 5);
            $table->decimal('saldo_agregat', 18, 2)->default(0);
            $table->integer('hari_tunggakan')->default(0);
            $table->tinyInteger('col')->default(1);
            $table->timestamps();

            $table->unique(['ckpn_run_id', 'periode', 'nokontrak']);
            $table->index(['ckpn_run_id', 'bucket']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
