<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role per kode akad (pokpby) untuk penentuan EAD dan populasi CKPN.
     */
    public function up(): void
    {
        Schema::create('ckpn_akad_role', function (Blueprint $table) {
            $table->string('pokpby', 10)->primary();
            $table->boolean('ead_osmdlc')->default(false);
            $table->boolean('ead_osmgnc')->default(false);
            $table->boolean('ead_tgkmdl')->default(false);
            $table->boolean('ead_tgkmgn')->default(false);
            $table->string('syarat_masuk', 20)->default('selalu');
            $table->timestamps();

            $table->foreign('pokpby')
                ->references('pokpby')
                ->on('kode_akad')
                ->onDelete('cascade');
        });
    }
};
