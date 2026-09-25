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
        Schema::create('pembiayaan', function (Blueprint $table) {
            $table->string('nokontrak', 30)->primary();
            $table->string('nocif', 20)->index();
            $table->string('nama', 100)->index();
            $table->string('kdprd', 10)->index();
            $table->string('kdloc', 5)->index();
            $table->string('pokpby', 10)->index();
            $table->char('gunadeb', 1)->index();
            $table->char('tglwo', 8)->nullable();
            $table->timestamps();

            $table->index(['kdprd', 'kdloc', 'pokpby']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
