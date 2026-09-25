<?php

namespace App\Imports;

use App\Enums\JenisUpload;
use Closure;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\ODS\Reader as ODSReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

/**
 * Import streaming bervolume tinggi menggunakan OpenSpout (O(1) memory).
 *
 * Menggantikan PhpSpreadsheet/Maatwebsite DOM parsing yang boros memori & CPU
 * sehingga mampu memproses >100.000 baris secara cepat tanpa memory spikes.
 */
class UploadDataImport
{
    /**
     * @param  callable(array<string, mixed>, int): void  $handler  Menerima data baris dan nomor baris.
     */
    public function __construct(
        private readonly JenisUpload $jenisUpload,
        private readonly Closure $handler,
    ) {}

    /**
     * Parse dan stream baris-baris file langsung ke handler callback.
     *
     * @throws \InvalidArgumentException
     */
    public function import(string $filePath): void
    {
        $reader = $this->createReader($filePath);
        $reader->open($filePath);

        $headerMap = null;
        $columns = $this->jenisUpload->kolom();
        $rowNumber = 0;
        $dataRowCount = 0;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $cells = $row->toArray();

                    // Baris pertama selalu dianggap sebagai baris header kolom.
                    if ($headerMap === null) {
                        $headings = array_map(static fn (mixed $v): string => (string) ($v ?? ''), $cells);
                        $headerMap = $this->normalizeHeadings($headings, $columns);
                        $missing = array_values(array_diff($this->jenisUpload->kolomWajib(), array_values($headerMap)));

                        if ($missing !== []) {
                            throw new \InvalidArgumentException('Kolom wajib tidak ditemukan: '.implode(', ', $missing));
                        }

                        continue;
                    }

                    $dataRowCount++;
                    $rowData = [];

                    foreach ($cells as $index => $value) {
                        if (isset($headerMap[$index])) {
                            $rowData[$headerMap[$index]] = $value;
                        }
                    }

                    ($this->handler)(
                        array_intersect_key($rowData, array_flip($columns)),
                        $rowNumber,
                    );
                }

                // Hanya baca worksheet pertama.
                break;
            }
        } finally {
            $reader->close();
        }

        if ($headerMap === null || $dataRowCount === 0) {
            throw new \InvalidArgumentException('File tidak memiliki baris data.');
        }
    }

    private function createReader(string $filePath): ReaderInterface
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'txt' => new CSVReader,
            'ods' => new ODSReader,
            default => new XLSXReader,
        };
    }

    /**
     * Petakan index kolom sel ke nama kolom baku model.
     *
     * @param  array<int, string>  $headings
     * @param  array<int, string>  $required
     * @return array<int, string>
     */
    private function normalizeHeadings(array $headings, array $required): array
    {
        $aliases = [
            'nokontrak' => ['no kontrak', 'nomor kontrak', 'no akad'],
            'nocif' => ['no cif', 'nomor cif', 'cif'],
            'noreg' => ['no reg', 'nomor registrasi', 'nomor register'],
            'kdloc' => ['kode kantor', 'kode lokasi'],
            'kdprd' => ['kode produk'],
            'pokpby' => ['jenis pembiayaan', 'akad'],
            'gunadeb' => ['guna debitur', 'penggunaan'],
            'tgltaks' => ['tanggal taksasi'],
            'nominallikuid' => ['nominal likuidasi', 'nilai likuidasi'],
        ];
        $lookup = [];

        foreach ($required as $column) {
            $lookup[$this->headingKey($column)] = $column;
            foreach ($aliases[$column] ?? [] as $alias) {
                $lookup[$this->headingKey($alias)] = $column;
            }
        }

        $normalized = [];
        foreach ($headings as $index => $heading) {
            $key = $this->headingKey($heading);
            if (isset($lookup[$key])) {
                $normalized[$index] = $lookup[$key];
            }
        }

        return $normalized;
    }

    private function headingKey(string $heading): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $heading));
    }
}
