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
        Schema::create('history_pembiayaan', function (Blueprint $table) {
            $table->id();
            $table->string('nokontrak', 30)->index();
            $table->string('kdprd', 10)->index();
            $table->string('kdloc', 5)->index();
            $table->string('pokpby', 10)->index();
            $table->char('tglexp', 8)->nullable();
            $table->decimal('osmdlc', 18, 2)->default(0);
            $table->decimal('osmgnc', 18, 2)->default(0);
            $table->decimal('tgkmdl', 18, 2)->default(0);
            $table->decimal('tgkmgn', 18, 2)->default(0);
            $table->integer('haritgk')->default(0)->index();
            $table->tinyInteger('col')->default(1)->index();
            $table->char('stsrec', 1)->default('A');
            $table->char('stsacc', 2)->nullable()->index();
            $table->decimal('ppka', 18, 2)->default(0);
            $table->char('periode', 6)->index();
            $table->timestamps();

            $table->unique(['nokontrak', 'periode']);
            $table->index(['periode', 'kdloc']);
            $table->index(['periode', 'pokpby']);
            $table->index(['periode', 'kdprd']);
            $table->index(['periode', 'col']);
        });
    }

    /**
     * Reverse the migrations.
     */
};
