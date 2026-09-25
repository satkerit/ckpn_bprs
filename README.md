# CKPN BABEL

Aplikasi perhitungan **CKPN (Cadangan Kerugian Penurunan Nilai)** pembiayaan syariah berbasis **PSAK 414** dan penyelarasan **PPKA (POJK 24/2024)**.

Formula:

```
CKPN = EAD × PD × LGD
```

PD memakai metode **Netflow** atau **Migration**. Keduanya membaca snapshot populasi yang sama.

## Stack

- PHP 8.3+, Laravel 13
- Livewire 4, Tailwind CSS 4, Vite 8
- MySQL (produksi) / SQLite (pengujian)
- Spatie Permission, Maatwebsite Excel, OpenSpout
- Queue database untuk unggahan besar

## Modul

| Menu | Route | Fungsi |
|---|---|---|
| Dashboard | `/dashboard` | Ringkasan pembiayaan, periode, run terakhir |
| Periode | `/periode` | Buka, kunci, finalisasi periode penilaian |
| Data | `/data/{jenis}` | Pembiayaan, history, jaminan, kantor, produk, setup jaminan |
| PD Netflow | `/pd/netflow` | PD roll-rate, lookback 12/24/36/60 bulan |
| PD Migration | `/pd/migration` | PD matriks migrasi, snapshot sama dengan Netflow |
| LGD | `/lgd` | Collateral shortfall atau expected recovery |
| CKPN | `/ckpn` | Hitung, ringkas per kantor, ekspor hasil |
| Master kode akad | `/master/kode-akad` | Referensi `pokpby` |
| Role CKPN | `/setup/ckpn-role` | Komponen EAD dan syarat masuk per akad |
| Admin | `/admin/*` | User, role, permission, audit log |

Jenis data: `pembiayaan`, `history`, `jaminan`, `kantor`, `produk`, `jaminan_setup`.

## Role CKPN per akad

Di `/setup/ckpn-role`, tiap `pokpby` mengatur:

- **Komponen EAD** — jumlah kolom history: `osmdlc`, `osmgnc`, `tgkmdl`, `tgkmgn`.
- **Syarat masuk populasi**:
  - `selalu` — rekening aktif, belum write-off
  - `ada_tunggakan` — hanya bila `tgkmdl > 0`
  - `jatuh_tempo` — `haritgk`, tunggakan, atau `tglexp` sudah lewat akhir bulan periode

Aturan yang sama dipakai oleh:

- perhitungan EAD dan CKPN
- snapshot **PD Netflow**
- snapshot **PD Migration**

Default seeder (`AkadRoleSeeder`), sesuai `docs/catatan.md`:

| pokpby | Akad | EAD | Syarat |
|---|---|---|---|
| 06 | Murabahah | osmdlc + tgkmgn | selalu |
| 13 | Multijasa | osmdlc + tgkmgn | selalu |
| 03 | Musyarakah | osmdlc + tgkmgn | jatuh tempo |
| 10 | IMBT | tgkmdl | jatuh tempo |

Akad di luar tabel memakai fallback `config/ckpn.php` sampai role-nya disimpan.

## Unggah data

- Template Excel per jenis dari halaman data.
- File besar diproses antrean (`ProcessUploadBatch`), progres di batch.
- Validasi baris sama dengan input manual.
- **Kosongkan data** (hanya modul pembiayaan): menghapus `pembiayaan` dan `agunan`. `history_pembiayaan` tidak ikut.

## Peran pengguna

| Peran | Akses |
|---|---|
| Administrator | Semua, termasuk user/role/permission dan role CKPN |
| Manajer Risiko | Hitung PD, LGD, CKPN, unggah data. Tanpa kelola user |
| Analis | Baca + input LGD |
| Operator Data | Unggah data |

Izin granular memakai Spatie Permission (`dashboard.view`, `pd.calculate`, `ckpn.export`, `setup.manage`, `upload.*`, dan seterusnya).

## Menjalankan

Butuh PHP 8.3+, Composer, Node.js, dan MySQL (atau SQLite untuk lokal).

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Atur `DB_*` di `.env`, lalu:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Unggahan memakai antrean database. Jalankan worker:

```bash
php artisan queue:work
```

Akun awal dari `AdminUserSeeder` (email di seeder, password `password`). Ganti segera.

Pengembangan aset:

```bash
composer run dev
```

## Pengujian

```bash
php artisan test
vendor/bin/pint --dirty
```

## Dokumen metodologi

- [docs/catatan.md](docs/catatan.md) — EAD per akad, PD, LGD, PPKA
- [docs/CATATAN CKPN.txt](docs/CATATAN%20CKPN.txt) — struktur tabel dan alur fitur
- [docs/audit-code-2026-09-25.md](docs/audit-code-2026-09-25.md) — audit kode terakhir

## Keamanan

- Seluruh modul di belakang login. Throttle pada login, unggah, dan ekspor.
- Unggahan disimpan di disk privat, dibatasi tipe dan ukuran, dihapus setelah diproses.
- Header keamanan: `X-Frame-Options`, `nosniff`, `Referrer-Policy`, HSTS bila HTTPS.
- Perubahan penting tercatat di audit log.
