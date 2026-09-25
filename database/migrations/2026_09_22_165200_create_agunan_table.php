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
        Schema::create('agunan', function (Blueprint $table) {
            $table->id();
            $table->string('nokontrak', 30)->index();
            $table->string('noreg', 30);
            $table->integer('urut')->default(1);
            $table->char('tgltaks', 8)->nullable();
            $table->string('jnsjamin', 20)->index();
            $table->decimal('nominallikuid', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['nokontrak', 'noreg', 'urut']);
            $table->index(['nokontrak', 'jnsjamin']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
