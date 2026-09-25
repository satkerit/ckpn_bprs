<?php

namespace App\Exports;

use App\Models\UploadBatch;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Laporan baris gagal pada satu batch unggahan: nomor baris Excel, pesan
 * kesalahan, dan nilai kolom sesuai template.
 *
 * S1 FIX: Sanitasi formula injection dengan memprefix karakter formula
 * (=, +, -, @) dengan apostrophe untuk mencegah formula execution di Excel.
 */
class UploadErrorExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private readonly UploadBatch $batch) {}

    public function collection(): Enumerable
    {
        $columns = $this->batch->jenis->kolom();

        return collect($this->batch->error_log ?? [])->map(function (array $error) use ($columns): array {
            $row = $error['row'] ?? [];

            return array_merge([
                $error['baris'] ?? null,
                $this->sanitizeFormulaInjection($error['kolom'] ?? '-'),
                $this->sanitizeFormulaInjection($error['nilai'] ?? '-'),
                $this->sanitizeFormulaInjection($error['message'] ?? 'Baris gagal diproses.'),
            ], array_map(
                fn (string $column): mixed => $this->sanitizeFormulaInjection($row[$column] ?? null),
                $columns,
            ));
        })->values();
    }

    public function headings(): array
    {
        return array_merge(['baris', 'kolom_masalah', 'nilai_masalah', 'pesan_kesalahan'], $this->batch->jenis->kolom());
    }

    /**
     * S1 FIX: Sanitasi formula injection dengan memprefix karakter formula
     * (=, +, -, @, Tab, CR) dengan apostrophe untuk mencegah formula execution.
     *
     * @param  mixed  $value  Nilai sel yang akan disanitasi.
     * @return mixed Nilai yang sudah disanitasi atau original jika bukan string.
     */
    private function sanitizeFormulaInjection(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        // Prefix dengan apostrophe jika value dimulai dengan karakter formula yang berbahaya
        if (preg_match('/^[\=\+\-\@\t\r]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
