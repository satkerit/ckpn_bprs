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
        Schema::create('ckpn_run', function (Blueprint $table) {
            $table->id();
            $table->char('periode', 6)->index();
            $table->unsignedTinyInteger('lookback_bulan')->default(12);
            $table->string('metode', 20)->default('netflow');
            $table->string('metode_lgd', 20)->default('shortfall');
            $table->string('status', 20)->default('queued')->index();
            $table->decimal('total_ead', 20, 2)->default(0);
            $table->decimal('total_ckpn', 20, 2)->default(0);
            $table->decimal('total_ppka', 20, 2)->default(0);
            $table->decimal('cadangan_ppka_tambahan', 20, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->longText('log')->nullable();
            $table->timestamps();

            $table->index(['periode', 'status']);
            $table->index(['periode', 'metode', 'metode_lgd']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
