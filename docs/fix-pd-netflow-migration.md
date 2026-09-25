# Fix perhitungan PD Netflow dan PD Migration

## Temuan audit

Pemeriksaan alur PD Netflow dan PD Migration menemukan empat masalah.

### 1. Matriks transisi tidak dirata-ratakan (menyimpang dari metodologi)

`docs/catatan.md` menetapkan:

```
PD Netflow Average = (Matriks Transisi_1 + ... + Matriks Transisi_N) / N
```

Kode sekarang merantai seluruh matriks bulanan menjadi satu rantai Markov (compound) dan memakai bobot mentahnya. Dua penyimpangan:

- Bobot per bulan diambil apa adanya. Periode dengan sedikit data mendapat bobot sama dengan periode padat, sehingga PD bergerak liar.
- Rata-rata aritmetik (sesuai dokumen) tidak pernah dihitung.

Perbaikan: rata-ratakan matriks transisi per bucket asal terlebih dahulu, lalu jalankan rantai Markov pada matriks rata-rata itu. Satu bulan pertama tetap memakai matriks bulan pertama, sesuai alur dokumen.

### 2. Segmentasi run diabaikan

`CkpnCalculator` memakai dimensi segmentasi dari `ckpn_segment` milik run, tetapi `RollRateCalculator::segment()` selalu membentuk kunci empat dimensi (`kdloc|pokpby|gunadeb|kdprd`). Saat user memilih segmentasi satu atau dua dimensi, kunci PD tidak akan cocok dengan kunci rekening di `CkpnCalculator`, sehingga PD terbaca nol.

Perbaikan: `RollRateCalculator::build()` menerima daftar dimensi dari run dan membentuk kunci segmentasi memakai `SegmentKeyBuilder` yang sama dengan `CkpnCalculator`.

### 3. Job `BuildRollRate` tidak pernah dipakai

`app/Jobs/BuildRollRate.php` tidak di-dispatch dari mana pun. UI memanggil `RollRateCalculator::build()` langsung. Job itu juga memuat seluruh window lookback sekaligus, berlawanan dengan perbaikan query per-pasangan-bulan yang sudah diterapkan di `build()`.

Perbaikan: hapus job tersebut.

### 4. `basis_pd` tidak diterapkan

`config/ckpn.php` mendefinisikan `basis_pd` (debitur atau saldo), tetapi kedua kalkulator selalu membagi dengan `jumlah_rekening`. Kolom `total_saldo` tersimpan di `ckpn_roll_rate` tapi tidak pernah dipakai.

Perbaikan: kalkulator memakai pembagi sesuai `basis_pd`.

### 5. Test usang

Sembilan test di `tests/Unit/CkpnCalculationTest.php` gagal karena memanggil `new EadCalculator` tanpa argumen. `EadCalculator` sudah mewajibkan `AkadRoleResolver`. Dua test lama sekaligus mengunci perilaku compound yang menyimpang.

Perbaikan: sesuaikan test ke API terbaru dan kunci perilaku rata-rata.

## Verifikasi

- `php artisan test` — suite lengkap.
- `vendor/bin/pint --dirty` — bersih.
- Tambah test untuk rata-rata matriks, segmentasi dinamis, dan `basis_pd` saldo.
