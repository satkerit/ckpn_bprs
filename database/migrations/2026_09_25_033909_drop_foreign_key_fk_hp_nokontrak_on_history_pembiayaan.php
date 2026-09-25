<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus foreign key fk_hp_nokontrak agar upload dan pencatatan history
     * pembiayaan tidak dibatasi oleh ketersediaan data di master pembiayaan.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $foreign = $driver === 'sqlite' ? ['nokontrak'] : 'fk_hp_nokontrak';

        Schema::table('history_pembiayaan', function (Blueprint $table) use ($foreign) {
            $table->dropForeign($foreign);
        });
    }
};
