<?php

namespace App\Exports;

use App\Enums\JenisUpload;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateUploadExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly JenisUpload $jenisUpload) {}

    /**
     * Template hanya berisi baris heading. Baris contoh tidak ditulis ke sheet
     * karena akan terbaca sebagai data saat file diunggah kembali.
     */
    public function collection(): Enumerable
    {
        return collect();
    }

    public function headings(): array
    {
        return $this->jenisUpload->kolom();
    }
}
