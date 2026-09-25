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
        Schema::create('setup_parameter', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 100)->unique();
            $table->longText('nilai')->nullable();
            $table->string('group_key', 50)->default('umum');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['group_key', 'kunci']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
