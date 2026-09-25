# Audit Kode CKPN BABEL

Tanggal: 2026-09-25  
Lingkup: `app/`, `routes/`, `resources/views/`, `database/migrations/`, `tests/`  
Metode: audit statik (dead code, duplikasi, efektivitas, performa, keamanan OWASP Top 10). Tidak ada perubahan kode aplikasi.

## Ringkasan

| Area | Status | Catatan |
|---|---|---|
| Dead code | Rendah | Tidak ada class/file yatim. Beberapa method privat berpotensi tidak terpakai. |
| Duplikasi | Rendah–sedang | Pola request data dan kalkulasi LGD berulang, masih terkontrol. |
| Efektivitas | Sedang | Chunk + bulk upsert sudah baik. Satu filter di memori pada roll rate. |
| Performa | Sedang | N+1 di LGD expected recovery. Index history belum optimal. |
| Keamanan | Sedang | Gate, CSRF, throttle login, header keamanan sudah ada. CSP belum. Export error log tanpa sanitasi formula. |

Tidak ada SQL mentah dari input user. `selectRaw` di [Ckpn/Index.php](file:///d:/laragon/www/CKPN_BABEL/app/Livewire/Ckpn/Index.php#L32) memakai binding.

---

## 1. Dead code

Tidak ditemukan class PHP di `app/` yang tidak direferensikan.

| Item | Lokasi | Temuan |
|---|---|---|
| `UploadDataImport` | [UploadDataImport.php](file:///d:/laragon/www/CKPN_BABEL/app/Imports/UploadDataImport.php) | Dipakai [ImportDataService.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Upload/ImportDataService.php). Bukan dead code. |
| `calculate()` | [CkpnCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/CkpnCalculator.php#L148) | Dipakai `calculateRun`. Bukan dead code. |
| `expectedRecovery()` | [LgdCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/LgdCalculator.php#L93) | Dipakai path metode Expected Recovery. Bukan dead code. |

Sisa yang perlu dicek manual sebelum dihapus: helper privat di form request dan export yang hanya dipakai satu cabang. Jangan hapus tanpa cek pemanggilan.

## 2. Duplikasi dan kode tidak reusable

| Item | Lokasi | Dampak |
|---|---|---|
| Enam form request data | `app/Http/Requests/Data/*` | Pola `rules()` / `messages()` mirip. Sudah ada induk [DataRequest.php](file:///d:/laragon/www/CKPN_BABEL/app/Http/Requests/Data/DataRequest.php). Cukup, jangan abstraksi lagi kecuali rule baru berulang. |
| Lookup haircut agunan | [LgdCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/LgdCalculator.php#L171) | Filter koleksi diulang per agunan. Masih satu class. |
| Segment key | [CkpnCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/CkpnCalculator.php#L156) dan [RollRateCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/RollRateCalculator.php#L84) | Format `dimensi=nilai` ditulis dua kali. Risiko drift bila dimensi bertambah. |

Rekomendasi: satukan builder segment key hanya jika dimensi ketiga ditambah. Sekarang duplikasi kecil, jangan refactor besar.

## 3. Efektivitas dan performa

### Sudah baik

- Upload streaming OpenSpout, chunk 1000, bulk upsert — [ImportDataService.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Upload/ImportDataService.php).
- Query log dimatikan di job — [ProcessUploadBatch.php](file:///d:/laragon/www/CKPN_BABEL/app/Jobs/ProcessUploadBatch.php#L29).
- Kalkulasi CKPN chunk 200 + eager load history per periode — [CkpnCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/CkpnCalculator.php#L56).
- Lazy loading dicegah di non-produksi — [AppServiceProvider.php](file:///d:/laragon/www/CKPN_BABEL/app/Providers/AppServiceProvider.php#L28).

### Temuan

| ID | Severity | Temuan | Lokasi |
|---|---|---|---|
| P1 | Tinggi | `expectedRecovery()` query likuidasi per rekening di dalam loop. Portofolio besar = N query. | [LgdCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/LgdCalculator.php#L98) |
| P2 | Sedang | Roll rate memuat seluruh history lookback ke memori, lalu filter koleksi. Lookback 60 bulan × ratusan ribu baris berisiko memori. | [RollRateCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/RollRateCalculator.php#L20) |
| P3 | Sedang | `PdNetflowCalculator` memfilter koleksi penuh di dalam loop segment. Kompleksitas mendekati O(segmen × baris). | [PdNetflowCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/PdNetflowCalculator.php#L28) |
| P4 | Sedang | Index `(nokontrak, periode)` pada `history_pembiayaan` perlu dipastikan ada. Tanpa itu, filter periode per chunk lambat. | migrasi history |
| P5 | Rendah | `chunkById` pada model PK string (`nokontrak`) tetap benar, tapi sort string, bukan urutan numerik. | [CkpnCalculator.php](file:///d:/laragon/www/CKPN_BABEL/app/Services/Ckpn/CkpnCalculator.php#L61) |

Rekomendasi P1: preload `CkpnLgdAgunanLikuidasi` untuk seluruh `nokontrak` chunk, keyBy `nokontrak`, jangan query di dalam `expectedRecovery`.

Rekomendasi P2: proses per pasangan periode (`T-1` → `T`) dengan `whereIn('periode', [$asal, $tujuan])`, bukan satu `get()` untuk seluruh window.

## 4. Keamanan

Kontrol yang sudah ada:

- Route data di belakang `auth` — [web.php](file:///d:/laragon/www/CKPN_BABEL/routes/web.php).
- Gate per jenis upload dan `setup.manage`.
- Login: session regenerate, throttle, audit gagal/berhasil — [LoginController.php](file:///d:/laragon/www/CKPN_BABEL/app/Http/Controllers/Auth/LoginController.php).
- Header: `X-Frame-Options`, `nosniff`, `Referrer-Policy`, HSTS bila HTTPS — [SecurityHeaders.php](file:///d:/laragon/www/CKPN_BABEL/app/Http/Middleware/SecurityHeaders.php).
- Upload: `mimes` + `mimetypes`, maks 50 MB, file disimpan disk `local` (bukan public), dihapus setelah job — [UploadController.php](file:///d:/laragon/www/CKPN_BABEL/app/Http/Controllers/UploadController.php#L74).
- Truncate hanya jenis `pembiayaan`, butuh `upload.pembiayaan`, tercatat audit.

| ID | OWASP | Severity | Temuan | Lokasi | Perbaikan |
|---|---|---|---|---|---|
| S1 | A03 Injection (formula) | Sedang | Sel error upload ditulis apa adanya ke Excel/CSV. Nilai diawali `=`, `+`, `-`, `@` bisa jadi formula saat dibuka. | [UploadErrorExport.php](file:///d:/laragon/www/CKPN_BABEL/app/Exports/UploadErrorExport.php#L23) | Prefix `'` pada sel yang diawali karakter formula. |
| S2 | A05 Misconfig | Sedang | Tidak ada Content-Security-Policy. Skrip inline di halaman data tetap jalan. | [SecurityHeaders.php](file:///d:/laragon/www/CKPN_BABEL/app/Http/Middleware/SecurityHeaders.php) | Tambah CSP ketat setelah skrip inline dipindah. |
| S3 | A01 Access | Rendah | `DataRequest::authorize()` mengembalikan `true`. Otorisasi hanya di controller. Request yang dipakai di luar controller tidak menolak sendiri. | [DataRequest.php](file:///d:/laragon/www/CKPN_BABEL/app/Http/Requests/Data/DataRequest.php#L21) | Pertahankan Gate di controller. Jangan panggil request ini dari job tanpa cek ulang. |
| S4 | A04 Design | Rendah | Truncate menghapus master `pembiayaan` permanen (bukan soft delete) sementara history tetap. Rekening history jadi yatim. | [DataController.php](file:///d:/laragon/www/CKPN_BABEL/app/Http/Controllers/DataController.php) | Sengaja sesuai permintaan. Dokumentasikan bahwa history tidak ikut FK setelah drop `fk_hp_nokontrak`. |
| S5 | A09 Logging | Rendah | `error_log` job menyimpan pesan exception. Jangan tampilkan trace ke user (sudah tidak). | [ProcessUploadBatch.php](file:///d:/laragon/www/CKPN_BABEL/app/Jobs/ProcessUploadBatch.php#L67) | Pertahankan trace hanya di log server. |
| S6 | A07 Auth | Info | Tidak ada 2FA. Cukup untuk intranet BPRS bila password policy dan throttle aktif. | login | Tambah 2FA hanya jika akses dari internet. |

Tidak ditemukan:

- Query builder dengan string konkatenasi dari request.
- `{!! !!}` pada output data user di view yang ditinjau.
- Mass assignment di luar `$fillable`.
- File upload ke disk public.
- Open redirect di `intended()`.

## 5. Prioritas perbaikan

1. **P1** — preload likuidasi LGD, hilangkan query per rekening.
2. **S1** — sanitasi formula pada export error upload.
3. **P2** — roll rate per pasangan bulan, jangan muat seluruh lookback sekaligus.
4. **P4** — pastikan index `history_pembiayaan (nokontrak, periode)` dan `(periode, pokpby)`.
5. **S2** — CSP setelah skrip inline dirapikan.

Jangan dikerjakan dalam audit ini: refactor form request, penggabungan segment key, penghapusan method yang masih terpakai.
