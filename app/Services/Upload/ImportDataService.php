<?php

namespace App\Services\Upload;

use App\Enums\JenisUpload;
use App\Enums\StatusPeriode;
use App\Http\Requests\Data\DataRequest;
use App\Imports\UploadDataImport;
use App\Models\Agunan;
use App\Models\CkpnPeriode;
use App\Models\Kantor;
use App\Models\KodeAkad;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Models\SetupJaminan;
use App\Models\UploadBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportDataService
{
    /** Kolom bertipe angka; selain kolom ini selalu diperlakukan sebagai teks. */
    private const NUMERIC_COLUMNS = ['osmdlc', 'osmgnc', 'tgkmdl', 'tgkmgn', 'haritgk', 'col', 'ppka', 'nominallikuid', 'urut', 'bobot'];

    /** Kolom tanggal bertipe char(8) format Ymd. */
    private const DATE_COLUMNS = ['tglwo', 'tglexp', 'tgltaks'];

    /** Frekuensi flush progress ke database (setiap N baris). */
    private const PROGRESS_FLUSH_INTERVAL = 2000;

    /**
     * Jumlah baris valid yang ditahan di memori sebelum ditulis sekaligus
     * (bulk upsert). Satu perintah upsert untuk 1.000 baris menggantikan
     * 1.000 round-trip query, sehingga file dengan ratusan ribu baris tetap
     * selesai dalam hitungan detik, bukan menit.
     */
    private const WRITE_CHUNK_SIZE = 1000;

    /**
     * Batas jumlah rincian error yang disimpan ke kolom JSON. Baris gagal tetap
     * dihitung seluruhnya, hanya rinciannya yang dipotong agar file dengan
     * puluhan ribu baris salah tidak menghabiskan memori worker.
     */
    private const MAX_ERROR_PREVIEW = 500;

    /**
     * Import sinkronus (langsung, tanpa queue). Digunakan oleh controller lama
     * dan saat queue tidak dikonfigurasi.
     */
    public function import(UploadedFile $file, JenisUpload $jenisUpload): UploadBatch
    {
        $totalEstimasi = $this->hitungTotalBaris($file);

        $batch = UploadBatch::query()->create([
            'jenis' => $jenisUpload,
            'file_name' => $file->getClientOriginalName(),
            'status' => 'processing',
            'uploaded_by' => auth()->id(),
            'total_baris' => $totalEstimasi,
        ]);

        return $this->doImport($file, $jenisUpload, $batch, $totalEstimasi);
    }

    /**
     * Import dari batch yang sudah dibuat (dipanggil oleh ProcessUploadBatch Job).
     * File sudah tersimpan di storage, batch sudah ada dengan status 'queued'.
     */
    public function importFromBatch(UploadedFile $file, JenisUpload $jenisUpload, UploadBatch $batch): UploadBatch
    {
        $totalEstimasi = $this->hitungTotalBaris($file);

        $batch->update([
            'status' => 'processing',
            'total_baris' => $totalEstimasi,
        ]);

        return $this->doImport($file, $jenisUpload, $batch, $totalEstimasi);
    }

    /**
     * Inti proses import: baca file, validasi baris, simpan data, update status batch.
     *
     * Optimasi volume besar: referensi validasi (kdprd, kdloc, pokpby, dll.)
     * dimuat sekali ke memori, baris valid ditampung lalu ditulis dengan bulk
     * upsert per 1.000 baris, dan progress di-flush tiap 2.000 baris.
     */
    private function doImport(UploadedFile $file, JenisUpload $jenisUpload, UploadBatch $batch, ?int $totalEstimasi = null): UploadBatch
    {
        $processed = 0;
        $success = 0;
        $duplicates = 0;
        $failureCount = 0;
        $errors = [];
        $periodeTerdeteksi = [];
        $buffer = [];
        $seenKeys = [];
        $jaminanDihapus = [];

        // Muat seluruh nilai referensi validasi sekali di awal: rule exists
        // diganti Rule::in dari set ini, dan cek periode terkunci menjadi
        // lookup array, sehingga iterasi 100 ribu baris tidak menjalankan
        // ratusan ribu query per baris.
        $lookupCache = $this->preloadReferenceCache($jenisUpload);
        $now = now()->toDateTimeString();

        try {
            $totalEstimasi ??= $this->hitungTotalBaris($file);

            if ($totalEstimasi === 0) {
                throw new \InvalidArgumentException('File tidak memiliki baris data.');
            }

            $import = new UploadDataImport($jenisUpload, function (array $row, int $baris) use (
                $jenisUpload, $batch, &$lookupCache, $now,
                &$processed, &$success, &$duplicates, &$failureCount, &$errors, &$periodeTerdeteksi, &$buffer, &$seenKeys
            ): void {
                // Baris tanpa satu pun nilai (mis. sisa baris kosong Excel) dilewati.
                if ($this->isEmptyRow($row)) {
                    return;
                }

                try {
                    $row = $this->normalizeValues($row);

                    // Periode hanya dibaca dari kolom data, bukan dari input pengguna.
                    $rowPeriode = $this->resolvePeriode($jenisUpload, $row, $lookupCache);

                    // Rule identik dengan input manual, sehingga kesalahan tampil
                    // sebagai pesan validasi (bukan error database).
                    $validatedRow = DataRequest::validateRow($jenisUpload, $row, $lookupCache);

                    // Satu kunci unik hanya boleh muncul sekali per periode di dalam
                    // satu file. Kemunculan berikutnya ditolak agar data ganda tidak
                    // saling menimpa tanpa jejak.
                    $uniqueKey = $this->resolveUniqueKey($jenisUpload, $validatedRow, $rowPeriode);

                    if ($uniqueKey !== null) {
                        if (isset($seenKeys[$uniqueKey])) {
                            throw new \InvalidArgumentException($this->duplicateMessage(
                                $jenisUpload,
                                $validatedRow,
                                $rowPeriode,
                                $seenKeys[$uniqueKey],
                            ));
                        }

                        $seenKeys[$uniqueKey] = $baris;
                    }

                    $periodeTerdeteksi[$rowPeriode ?? '-'] = true;
                } catch (ValidationException $exception) {
                    $failureCount++;
                    if (count($errors) < self::MAX_ERROR_PREVIEW) {
                        $errors[] = $this->errorEntry($baris, $row, $exception->validator->errors()->toArray());
                    }
                    $processed++;

                    return;
                } catch (\Throwable $exception) {
                    if (str_contains($exception->getMessage(), 'sudah terkunci atau final')) {
                        throw $exception;
                    }

                    $failureCount++;
                    if (count($errors) < self::MAX_ERROR_PREVIEW) {
                        $errors[] = $this->errorEntry($baris, $row, [$exception->getMessage()]);
                    }
                    $processed++;

                    return;
                }

                // Baris valid ditampung untuk ditulis massal, bukan per baris.
                // Pada produk, jika kolom aktif kosong, pertahankan status lama dari database atau default true.
                if ($jenisUpload === JenisUpload::Produk && ! array_key_exists('aktif', $validatedRow)) {
                    $existingAktif = $lookupCache['produk_aktif'][$validatedRow['kdprd']] ?? true;
                    $validatedRow['aktif'] = (bool) $existingAktif;
                    $lookupCache['produk_aktif'][$validatedRow['kdprd']] = $validatedRow['aktif'];
                }

                if (in_array($jenisUpload, [JenisUpload::Pembiayaan, JenisUpload::History, JenisUpload::Jaminan], true)) {
                    $validatedRow['deleted_at'] = null;
                }

                $validatedRow['created_at'] = $now;
                $validatedRow['updated_at'] = $now;
                $buffer[] = $validatedRow;
                $processed++;

                if (count($buffer) >= self::WRITE_CHUNK_SIZE) {
                    $this->flushBuffer($jenisUpload, $buffer, $success, $duplicates);
                }

                // Flush progress ke database secara berkala agar polling realtime akurat.
                if ($processed % self::PROGRESS_FLUSH_INTERVAL === 0) {
                    $batch->updateQuietly([
                        'baris_diproses' => $processed,
                        'baris_sukses' => $success,
                        'baris_duplikat' => $duplicates,
                        'baris_gagal' => $failureCount,
                        'error_log' => $errors ? array_slice($errors, 0, 50) : null,
                    ]);
                    gc_collect_cycles();
                }
            });

            $import->import($file->getRealPath());

            // Tulis sisa baris di buffer setelah file habis.
            if ($buffer !== []) {
                $this->flushBuffer($jenisUpload, $buffer, $success, $duplicates);
            }

            $totalAktual = $processed;
            $batch->update([
                'periode' => $jenisUpload === JenisUpload::History && count($periodeTerdeteksi) === 1
                    ? array_key_first($periodeTerdeteksi)
                    : null,
                'total_baris' => $totalAktual,
                'baris_diproses' => $totalAktual,
                'baris_sukses' => $success,
                'baris_duplikat' => $duplicates,
                'baris_gagal' => $failureCount,
                'status' => $failureCount === 0 ? 'completed' : 'completed_with_errors',
                'error_log' => $errors ?: null,
            ]);
        } catch (\Throwable $exception) {
            $batch->update([
                'baris_diproses' => $processed,
                'baris_sukses' => $success,
                'baris_duplikat' => $duplicates,
                'baris_gagal' => $failureCount,
                'status' => 'failed',
                'error_log' => [['message' => $exception->getMessage()]],
            ]);
            throw $exception;
        }

        return $batch->refresh();
    }

    /** Estimasi total baris data (tanpa header) dari file Excel/CSV. */
    private function hitungTotalBaris(UploadedFile $file): int
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $info = $reader->listWorksheetInfo($file->getRealPath());

            return isset($info[0]['totalRows']) ? max(0, (int) $info[0]['totalRows'] - 1) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Satu entri log kegagalan baris, dipakai sebagai isi laporan error.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int|string, mixed>  $messages
     * @return array<string, mixed>
     */
    private function errorEntry(int $baris, array $row, array $messages, ?string $failedField = null): array
    {
        $flattenedMessages = [];
        $detectedField = $failedField;

        foreach ($messages as $field => $msg) {
            if (is_string($field) && ! is_numeric($field) && $detectedField === null) {
                $detectedField = $field;
            }
            if (is_array($msg)) {
                $flattenedMessages[] = implode(' ', $msg);
            } else {
                $flattenedMessages[] = (string) $msg;
            }
        }

        $failedValue = $detectedField !== null && array_key_exists($detectedField, $row)
            ? (is_scalar($row[$detectedField]) ? (string) $row[$detectedField] : json_encode($row[$detectedField]))
            : null;

        return [
            'baris' => $baris,
            'kolom' => $detectedField,
            'nilai' => $failedValue,
            'message' => implode(' ', array_slice($flattenedMessages, 0, 5)),
            'row' => $row,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Menyamakan nilai sel dengan perilaku input manual: teks dipangkas, sel
     * kosong menjadi null, tanggal dinormalkan ke Ymd/YYYYMM agar sesuai tipe
     * kolom, dan kolom aktif dibaca sebagai boolean.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeValues(array $row): array
    {
        $row = array_map(function (mixed $value): mixed {
            if (! is_string($value)) {
                return $value;
            }

            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }, $row);

        // Sel angka di Excel terbaca sebagai int/float. Kolom identitas seperti
        // nokontrak, gunadeb, atau kdloc harus tetap berupa teks agar lolos rule
        // validasi yang sama dengan input manual.
        foreach ($row as $column => $value) {
            $keepNumeric = in_array($column, self::NUMERIC_COLUMNS, true)
                || in_array($column, ['aktif', 'periode'], true);

            if (! $keepNumeric && (is_int($value) || is_float($value))) {
                $row[$column] = $this->numberToString($value);
            }
        }

        foreach (self::DATE_COLUMNS as $column) {
            if (array_key_exists($column, $row)) {
                $row[$column] = $this->toYmd($row[$column]);
            }
        }

        if (array_key_exists('periode', $row)) {
            $row['periode'] = $this->toPeriode($row['periode']);
        }

        if (array_key_exists('aktif', $row)) {
            $row['aktif'] = $this->toBoolean($row['aktif']);
        }

        return $row;
    }

    /** Nilai angka sel Excel menjadi teks tanpa ekor desimal yang tidak perlu. */
    private function numberToString(int|float $value): string
    {
        return is_float($value) && floor($value) === $value
            ? (string) (int) $value
            : (string) $value;
    }

    private function toYmd(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Ymd');
        }

        if ($this->isExcelDateSerial($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Ymd');
        }

        try {
            return Carbon::parse((string) $value)->format('Ymd');
        } catch (\Throwable) {
            return null;
        }
    }

    private function toPeriode(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Ym');
        }

        if ($this->isExcelDateSerial($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Ym');
        }

        $digits = (string) preg_replace('/\D/', '', (string) $value);

        return match (strlen($digits)) {
            6 => $digits,
            8 => substr($digits, 0, 6),
            default => null,
        };
    }

    /**
     * Nilai kolom aktif dari file: 1/0, ya/tidak, true/false, aktif/nonaktif.
     * Nilai yang tidak dikenali dibiarkan agar rule validasi yang menolaknya.
     */
    private function toBoolean(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'ya', 'y', 'yes', 'aktif' => true,
            '0', 'false', 'tidak', 't', 'no', 'nonaktif' => false,
            default => $value,
        };
    }

    /** Nomor seri tanggal Excel (mis. 46234 untuk 2026-09-30), bukan tahun-bulan. */
    private function isExcelDateSerial(mixed $value): bool
    {
        return is_numeric($value) && (float) $value > 0 && (float) $value <= 100000;
    }

    /**
     * Ambil periode dari kolom data (bukan dari input pengguna).
     *
     * Konteks upload hanya menyimpan data, bukan menetapkan periode perhitungan
     * CKPN. Karena itu periode yang belum terdaftar di tabel ckpn_periode tidak
     * diperlakukan sebagai kesalahan. Penolakan hanya dilakukan bila periode
     * sudah terkunci atau final, agar data pada periode yang dibekukan tidak
     * ikut berubah.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, array<string|int, mixed>>|null  $lookupCache
     */
    private function resolvePeriode(JenisUpload $jenisUpload, array $row, ?array $lookupCache = null): ?string
    {
        $periode = $row['periode'] ?? null;

        if ($periode === null || $periode === '') {
            abort_if($jenisUpload === JenisUpload::History, 422, 'Kolom periode wajib diisi pada data history pembiayaan.');

            return $periode;
        }

        $terkunci = $lookupCache !== null
            ? isset($lookupCache['periode_terkunci'][$periode])
            : CkpnPeriode::query()->where('periode', $periode)->first()?->isLocked() === true;

        abort_if($terkunci, 422, "Periode {$periode} sudah terkunci atau final.");

        return $periode;
    }

    /**
     * Muat nilai referensi validasi sekali di awal import.
     *
     * Dipakai untuk mengganti rule exists (query per baris) dan pengecekan
     * periode terkunci (query per baris) menjadi lookup array di memori.
     * Tanpa ini, file 100 ribu baris menjalankan ratusan ribu query.
     *
     * @return array<string, array<string|int, mixed>>
     */
    private function preloadReferenceCache(JenisUpload $jenisUpload): array
    {
        $cache = [
            'kdprd' => ProdukPembiayaan::query()->pluck('kdprd')->flip()->all(),
            'kdloc' => Kantor::query()->pluck('kdloc')->flip()->all(),
            'pokpby' => KodeAkad::query()->pluck('pokpby')->flip()->all(),
            'gunadeb' => DB::table('jenis_penggunaan')->pluck('kode')->flip()->all(),
            'kdjam' => SetupJaminan::query()->pluck('kdjam')->flip()->all(),
            'periode_terkunci' => CkpnPeriode::query()
                ->whereIn('status', [StatusPeriode::Locked->value, StatusPeriode::Final->value])
                ->pluck('periode')
                ->flip()
                ->all(),
        ];

        // Data jaminan boleh merujuk kontrak yang belum ada di master pembiayaan
        // (keterbatasan data master), sehingga daftar kontrak hanya dimuat saat
        // memang dipakai sebagai rule validasi.
        if ($jenisUpload === JenisUpload::Jaminan) {
            $cache['nokontrak'] = Pembiayaan::query()->pluck('nokontrak')->flip()->all();
        }

        if ($jenisUpload === JenisUpload::Produk) {
            $cache['produk_aktif'] = ProdukPembiayaan::query()->pluck('aktif', 'kdprd')->all();
        }

        return $cache;
    }

    /**
     * Tulis sekumpulan baris valid dengan satu perintah upsert.
     *
     * @param  array<int, array<string, mixed>>  $buffer
     */
    private function flushBuffer(JenisUpload $jenisUpload, array &$buffer, int &$success, int &$duplicates): void
    {
        if ($buffer === []) {
            return;
        }

        $uniqueBy = $this->uniqueKeys($jenisUpload);
        $updateColumns = $this->updateColumns($jenisUpload);

        // upsert() menyusun daftar kolom dari baris pertama buffer. Bila ada baris
        // dengan kolom yang tidak lengkap (mis. kolom opsional kosong), seluruh
        // perintah gagal. Samakan struktur kolom semua baris sebelum ditulis.
        $this->normalizeBufferColumns($buffer);

        DB::transaction(function () use ($jenisUpload, $buffer, $uniqueBy, $updateColumns, &$success, &$duplicates): void {
            if ($jenisUpload === JenisUpload::Jaminan) {
                // Jaminan diunggah sebagai satu set per kontrak: data lama kontrak
                // tersebut diganti seluruhnya oleh isi file yang baru diunggah.
                $this->replaceJaminanContracts($buffer);
            }

            $duplicates += $this->countExistingRows($jenisUpload, $buffer);
            $success += count($buffer);

            DB::table($this->tableName($jenisUpload))->upsert($buffer, $uniqueBy, $updateColumns);
        });

        $buffer = [];
    }

    /**
     * Samakan struktur kolom seluruh baris buffer.
     *
     * Setiap baris diberi nilai null untuk kolom yang tidak dimilikinya, dengan
     * union kolom dari semua baris sebagai acuan, sehingga satu perintah upsert
     * menerima baris dengan daftar kolom identik.
     *
     * @param  array<int, array<string, mixed>>  $buffer
     */
    private function normalizeBufferColumns(array &$buffer): void
    {
        $columns = [];

        foreach ($buffer as $row) {
            foreach ($row as $column => $value) {
                $columns[$column] = true;
            }
        }

        $template = array_fill_keys(array_keys($columns), null);

        foreach ($buffer as $index => $row) {
            $buffer[$index] = array_replace($template, $row);
        }
    }

    /**
     * Bentuk string kunci unik baris untuk deteksi duplikasi di dalam file yang sama.
     *
     * @param  array<string, mixed>  $row
     */
    private function resolveUniqueKey(JenisUpload $jenisUpload, array $row, ?string $periode = null): ?string
    {
        return match ($jenisUpload) {
            JenisUpload::Pembiayaan => isset($row['nokontrak']) ? (string) $row['nokontrak'] : null,
            JenisUpload::History => isset($row['nokontrak'], $periode) ? $row['nokontrak'].'@'.$periode : null,
            JenisUpload::Jaminan => isset($row['nokontrak'], $row['noreg'], $row['urut']) ? $row['nokontrak'].'@'.$row['noreg'].'@'.$row['urut'] : null,
            JenisUpload::Kantor => isset($row['kdloc']) ? (string) $row['kdloc'] : null,
            JenisUpload::Produk => isset($row['kdprd']) ? (string) $row['kdprd'] : null,
            JenisUpload::JaminanSetup => isset($row['kdjam']) ? (string) $row['kdjam'] : null,
        };
    }

    /**
     * Pesan error saat baris file memiliki nomor kontrak/kunci yang sama dengan baris sebelumnya.
     *
     * @param  array<string, mixed>  $row
     */
    private function duplicateMessage(JenisUpload $jenisUpload, array $row, ?string $periode, int $firstBaris): string
    {
        return match ($jenisUpload) {
            JenisUpload::History => "Nomor kontrak {$row['nokontrak']} duplikat pada periode {$periode} (sudah ada di baris {$firstBaris}).",
            JenisUpload::Pembiayaan => "Nomor kontrak {$row['nokontrak']} duplikat di dalam file (sudah ada di baris {$firstBaris}).",
            JenisUpload::Jaminan => "Agunan kontrak {$row['nokontrak']} nomor reg {$row['noreg']} urut {$row['urut']} duplikat di dalam file (sudah ada di baris {$firstBaris}).",
            default => "Data duplikat ditemukan di dalam file (sudah ada di baris {$firstBaris}).",
        };
    }

    /**
     * Hapus agunan lama untuk kontrak yang menjadi bagian dari buffer jaminan.
     *
     * @param  array<int, array<string, mixed>>  $buffer
     */
    private function replaceJaminanContracts(array $buffer): void
    {
        $contracts = [];

        foreach ($buffer as $row) {
            $contracts[$row['nokontrak']] = true;
        }

        Agunan::query()->whereIn('nokontrak', array_keys($contracts))->delete();
    }

    /**
     * Hitung baris yang sudah ada sebelum upsert, agar jumlah duplikat tetap
     * akurat tanpa query cek per baris.
     *
     * @param  array<int, array<string, mixed>>  $buffer
     */
    private function countExistingRows(JenisUpload $jenisUpload, array $buffer): int
    {
        $query = DB::table($this->tableName($jenisUpload));

        return match ($jenisUpload) {
            JenisUpload::Pembiayaan => $query->whereIn('nokontrak', array_column($buffer, 'nokontrak'))->count(),

            // History: cek pasangan nokontrak+periode secara tepat agar tidak
            // terjadi overcounting akibat cartesian product dua whereIn terpisah.
            JenisUpload::History => (function () use ($buffer): int {
                $count = 0;

                foreach ($buffer as $row) {
                    $count += DB::table('history_pembiayaan')
                        ->where('nokontrak', $row['nokontrak'])
                        ->where('periode', $row['periode'])
                        ->limit(1)
                        ->count();
                }

                return $count;
            })(),

            // Jaminan: cek kombinasi nokontrak+noreg+urut, sesuai unique key tabel.
            // (replaceJaminanContracts() sudah menghapus data lama per kontrak
            // sebelum ini, sehingga hasil 0 adalah normal pada alur replace.)
            JenisUpload::Jaminan => (function () use ($buffer): int {
                $count = 0;

                foreach ($buffer as $row) {
                    $count += DB::table('agunan')
                        ->where('nokontrak', $row['nokontrak'])
                        ->where('noreg', $row['noreg'])
                        ->where('urut', $row['urut'])
                        ->limit(1)
                        ->count();
                }

                return $count;
            })(),

            JenisUpload::Kantor => $query->whereIn('kdloc', array_column($buffer, 'kdloc'))->count(),
            JenisUpload::Produk => $query->whereIn('kdprd', array_column($buffer, 'kdprd'))->count(),
            JenisUpload::JaminanSetup => $query->whereIn('kdjam', array_column($buffer, 'kdjam'))->count(),
        };
    }

    private function tableName(JenisUpload $jenisUpload): string
    {
        return match ($jenisUpload) {
            JenisUpload::Pembiayaan => 'pembiayaan',
            JenisUpload::History => 'history_pembiayaan',
            JenisUpload::Jaminan => 'agunan',
            JenisUpload::Kantor => 'kantor',
            JenisUpload::Produk => 'produk_pembiayaan',
            JenisUpload::JaminanSetup => 'setup_jaminan',
        };
    }

    /**
     * Kunci unik tabel per jenis data; dipakai sebagai acuan ON DUPLICATE KEY
     * pada bulk upsert.
     *
     * @return array<int, string>
     */
    private function uniqueKeys(JenisUpload $jenisUpload): array
    {
        return match ($jenisUpload) {
            JenisUpload::Pembiayaan => ['nokontrak'],
            JenisUpload::History => ['nokontrak', 'periode'],
            JenisUpload::Jaminan => ['nokontrak', 'noreg', 'urut'],
            JenisUpload::Kantor => ['kdloc'],
            JenisUpload::Produk => ['kdprd'],
            JenisUpload::JaminanSetup => ['kdjam'],
        };
    }

    /**
     * Kolom yang diperbarui saat baris dengan kunci sama sudah ada.
     *
     * @return array<int, string>
     */
    private function updateColumns(JenisUpload $jenisUpload): array
    {
        return match ($jenisUpload) {
            JenisUpload::Pembiayaan => ['nocif', 'nama', 'kdprd', 'kdloc', 'pokpby', 'gunadeb', 'tglwo', 'deleted_at', 'updated_at'],
            JenisUpload::History => ['kdprd', 'kdloc', 'pokpby', 'tglexp', 'osmdlc', 'osmgnc', 'tgkmdl', 'tgkmgn', 'haritgk', 'col', 'stsrec', 'stsacc', 'ppka', 'deleted_at', 'updated_at'],
            JenisUpload::Jaminan => ['tgltaks', 'jnsjamin', 'nominallikuid', 'deleted_at', 'updated_at'],
            JenisUpload::Kantor => ['nama', 'alamat', 'updated_at'],
            JenisUpload::Produk => ['nama', 'pokpby', 'aktif', 'updated_at'],
            JenisUpload::JaminanSetup => ['ket', 'bobot', 'updated_at'],
        };
    }
}
