<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Perhitungan CKPN
    |--------------------------------------------------------------------------
    |
    | Nilai default metodologi perhitungan. Nilai yang dapat diubah pengguna
    | disimpan di tabel `setup_parameter`; nilai di sini hanya sebagai
    | fallback bila parameter belum diisi.
    |
    */

    'perhitungan' => [
        // Pilihan rentang observasi PD Netflow (dynamic lookback window), dalam bulan.
        'lookback_options' => [12, 24, 36, 60],

        // Rentang observasi default.
        'lookback_default' => 12,

        // Basis pembobotan probabilitas transisi: jumlah debitur atau saldo.
        'basis_pd' => [
            'debitur' => 'Jumlah Debitur',
            'saldo' => 'Saldo Outstanding',
        ],

        'basis_pd_default' => 'debitur',

        // Ambang hari tunggakan (haritgk) batas bawah tiap bucket.
        // Bucket 1 menerima haritgk <= batas bucket 2 (lihat BucketClassifierService).
        'bucket_haritgk' => [
            2 => 1,
            3 => 30,
            4 => 60,
            5 => 90,
            6 => 120,
            7 => 150,
            8 => 180,
            9 => 210,
            10 => 240,
            11 => 270,
            12 => 300,
            13 => 330,
        ],

        // Bucket khusus.
        'bucket_po' => 'PO',   // Paid off: rekening hilang dari snapshot berikutnya.
        'bucket_wo' => 'WO',   // Write-off.

        // Bucket yang dianggap loss (menjadi tujuan akhir flow to loss).
        'bucket_loss' => ['WO'],

        // Kolektibilitas minimal (col) yang selalu dianggap default pada saat penarikan
        // populasi LGD bila haritgk tidak tersedia.
        'col_default' => 3,

        // Hari tunggakan minimal agar rekening dianggap default untuk populasi LGD.
        'haritgk_default' => 90,

        // Batas observasi histori recovery LGD Expected Recovery, maksimal 5 tahun.
        'lgd_lookback_bulan' => 60,

        // Basis tunggakan EAD per kode akad (tabel kode_akad):
        //   margin       = hanya tunggakan margin/ujrah
        //   kondisional  = pokok + margin bila jatuh tempo, jika belum jatuh
        //                  tempo outstanding pokok tidak dihitung (PSAK 414)
        'akad_basis_tunggakan' => [
            '06' => 'margin',       // Murabahah
            '13' => 'margin',       // Ijarah Multijasa
            '03' => 'kondisional',  // Musyarakah
            '10' => 'kondisional',  // Ijarah Muntahiyah Bittamlik
        ],
        'akad_basis_default' => 'pokok_margin',

        // Kode akad yang berada di luar cakupan PSAK 414 ketika belum jatuh tempo.
        'akad_out_of_scope_lancar' => ['03', '10'],
    ],

];
