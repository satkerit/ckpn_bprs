<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upload_batch', function (Blueprint $table) {
            $table->unsignedInteger('baris_diproses')->default(0)->after('total_baris');
            $table->unsignedInteger('baris_duplikat')->default(0)->after('baris_gagal');
        });
    }
};
