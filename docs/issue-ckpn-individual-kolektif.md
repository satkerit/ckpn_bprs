# Issue: Kriteria CKPN Individual vs Kolektif

## Latar belakang

Tabel `ckpn_klasifikasi` dan enum `TipeCkpn` sudah ada. `CkpnCalculator` sudah membaca `tipe`: individual memakai `nilai_individual`, selain itu kolektif. Belum ada cara menetapkan klasifikasi itu. Permission `klasifikasi.manage` juga belum terpakai di route mana pun.

## Aturan yang diminta

Dari seluruh rekening NPF pada suatu periode, **hanya TOP N outstanding terbesar** yang menjadi CKPN Individual. Rekening NPF lainnya tetap CKPN Kolektif. Calon individual harus memenuhi **kedua** syarat berikut:

1. Kolektibilitas NPF: `history_pembiayaan.col` bernilai **3, 4, atau 5**.
2. Outstanding-nya berada dalam TOP N terbesar di antara semua rekening NPF pada periode itu. N diambil dari parameter `ckpn.individual_maks_rekening`.

Selain itu masuk CKPN Kolektif, termasuk:

- kol 1 dan 2;
- **rekening NPF yang outstanding-nya berada di bawah TOP N**.

## Parameter

Tambah satu parameter di `setup_parameter`, grup `ckpn`:

| Kunci | Tipe | Arti |
|---|---|---|
| `ckpn.individual_maks_rekening` | integer > 0 | Jumlah maksimal rekening NPF outstanding terbesar yang dijadikan individual |

Outstanding = `osmdlc + tgkmdl + tgkmgn` pada history periode tersebut (sama dengan `HistoryPembiayaan::bakiDebet()`).

## Perilaku

- Dijalankan per periode, sebelum atau saat run CKPN.
- Urutkan rekening dengan `col` 3/4/5 berdasarkan outstanding menurun.
- Ambil N teratas (`ckpn.individual_maks_rekening`). Simpan ke `ckpn_klasifikasi` dengan `tipe = individual`.
- Semua rekening lain pada periode itu `tipe = kolektif`, **termasuk NPF yang kalah peringkat (peringkat N+1 ke bawah)**.
- Rekening yang sudah punya `nilai_individual` manual tidak ditimpa nilainya; hanya `tipe` yang mengikuti aturan.
- Hasil bisa diubah manual per rekening (override) dan tercatat `ditentukan_oleh` + audit log.
- Perubahan parameter tidak mengubah periode yang statusnya `locked` atau `final`.

## Yang perlu dibangun

- UI setup parameter: input jumlah maksimal rekening individual.
- Layanan klasifikasi: terapkan aturan di atas untuk satu periode.
- Halaman daftar klasifikasi: filter periode, lihat tipe, outstanding, kolektibilitas, dan override manual.
- Hubungkan ke `CkpnCalculator` agar run memakai hasil klasifikasi terkini.
- Izin: `klasifikasi.view` untuk lihat, `klasifikasi.manage` untuk hitung ulang dan override.

## Kriteria selesai

- Parameter tersimpan dan tervalidasi (bilangan bulat, minimal 1).
- Untuk periode contoh: hanya N rekening NPF outstanding terbesar berstatus individual.
- Rekening kol 1 dan 2 selalu kolektif.
- Rekening NPF di peringkat N+1 ke bawah berstatus kolektif, walau NPF.
- Periode terkunci menolak hitung ulang.
- Test mencakup urutan outstanding, batas N, kol 1–2, dan periode terkunci.
