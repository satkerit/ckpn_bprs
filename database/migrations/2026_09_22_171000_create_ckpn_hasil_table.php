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
        Schema::create('ckpn_hasil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->char('periode', 6)->index();
            $table->string('nokontrak', 30)->index();
            $table->string('nocif', 20)->nullable();
            $table->string('nama', 100)->nullable();
            $table->string('kdloc', 5)->nullable();
            $table->string('pokpby', 10)->nullable();
            $table->string('kdprd', 10)->nullable();
            $table->char('gunadeb', 1)->nullable();
            $table->string('segment_key', 255)->default('')->index();
            $table->string('tipe_ckpn', 20)->default('kolektif');
            $table->boolean('in_scope_psak414')->default(true)->index();
            $table->string('bucket', 5)->nullable()->index();
            $table->decimal('ead', 20, 2)->default(0);
            $table->decimal('pd_netflow', 9, 6)->default(0);
            $table->decimal('lgd_persen', 9, 6)->default(0);
            $table->decimal('ckpn_psak414', 20, 2)->default(0);
            $table->decimal('ppka_wajib', 20, 2)->default(0);
            $table->decimal('selisih', 20, 2)->default(0);
            $table->decimal('cadangan_tambahan', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(['ckpn_run_id', 'nokontrak']);
            $table->index(['ckpn_run_id', 'periode', 'kdloc']);
            $table->index(['ckpn_run_id', 'periode', 'pokpby']);
            $table->index(['ckpn_run_id', 'tipe_ckpn']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
