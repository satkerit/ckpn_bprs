<?php

namespace App\Services\Ckpn;

/**
 * PD Netflow memakai kalkulator yang sama dengan PD Migration.
 *
 * Keduanya membaca snapshot transisi dari RollRateCalculator dan menghitung
 * rata-rata matriks bulanan sebelum menjalankan rantai Markov. Lihat
 * PdCalculator untuk rincian metodologinya.
 */
class PdNetflowCalculator extends PdCalculator {}
