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
        Schema::create('ckpn_bucket', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 5)->unique();
            $table->string('label', 50);
            $table->text('deskripsi')->nullable();
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
};
