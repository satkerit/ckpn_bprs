<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upload_batch', function (Blueprint $table) {
            // Path file sementara di storage/app/uploads/ untuk diproses oleh Job queue.
            $table->string('file_path', 255)->nullable()->after('file_name');
        });
    }
};
