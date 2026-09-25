<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * P4 FIX: Add composite index (nokontrak, periode) untuk optimize query pattern
     * pada CkpnLgdAgunanLikuidasi lookup dan RollRateCalculator filtering.
     */
    public function up(): void
    {
        Schema::table('history_pembiayaan', function (Blueprint $table) {
            // P4 FIX: Composite index untuk efficient filtering dalam:
            // - LgdCalculator::calculate() - filter by nokontrak + periode
            // - RollRateCalculator::build() - filter by periode + join nokontrak
            // - ImportDataService - validation queries
            if (! $this->indexExists('history_pembiayaan', 'idx_hp_nokontrak_periode')) {
                $table->index(['nokontrak', 'periode'], 'idx_hp_nokontrak_periode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_pembiayaan', function (Blueprint $table) {
            $table->dropIndex('idx_hp_nokontrak_periode');
        });
    }

    /**
     * Helper: Check if index exists to prevent duplicate index errors.
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);

        return isset($indexes[$index]);
    }
};
