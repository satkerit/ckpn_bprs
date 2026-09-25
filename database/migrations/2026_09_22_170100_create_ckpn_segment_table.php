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
        Schema::create('ckpn_segment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ckpn_run_id')->constrained('ckpn_run')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->string('dimensi', 20);
            $table->string('nilai', 100)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('ckpn_segment')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['ckpn_run_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
