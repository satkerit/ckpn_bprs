<?php

namespace App\Services\Ckpn;

/**
 * PD Migration memakai kalkulator yang sama dengan PD Netflow.
 *
 * Matriks transisi bulanan dirata-ratakan secara aritmetik terlebih dahulu,
 * lalu rantai Markov dijalankan pada matriks rata-rata tersebut. Lihat
 * PdCalculator untuk rincian metodologinya.
 */
class PdMigrationCalculator extends PdCalculator {}
