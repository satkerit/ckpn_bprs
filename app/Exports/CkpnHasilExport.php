<?php

namespace App\Exports;

use App\Models\CkpnHasil;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export hasil CKPN menggunakan FromQuery + chunking sehingga tidak
 * memuat seluruh dataset ke memori sekaligus. Aman untuk data besar.
 */
class CkpnHasilExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings
{
    public function __construct(private readonly ?int $runId = null) {}

    /**
     * Query yang di-chunk otomatis oleh maatwebsite/excel.
     * Tidak memanggil get() sehingga penggunaan memori jauh lebih efisien.
     */
    public function query(): Builder
    {
        return CkpnHasil::query()
            ->when($this->runId, fn ($query): mixed => $query->where('ckpn_run_id', $this->runId))
            ->orderBy('id')
            ->select([
                'periode', 'nokontrak', 'nocif', 'nama', 'kdloc', 'pokpby', 'kdprd', 'gunadeb',
                'segment_key', 'tipe_ckpn', 'in_scope_psak414', 'bucket', 'ead', 'pd_netflow',
                'lgd_persen', 'ckpn_psak414', 'ppka_wajib', 'selisih', 'cadangan_tambahan',
            ]);
    }

    /**
     * Ukuran chunk per-batch; sesuaikan EXCEL_CHUNK_SIZE di .env jika perlu.
     */
    public function chunkSize(): int
    {
        return (int) config('excel.exports.chunk_size', 1000);
    }

    public function headings(): array
    {
        return [
            'Periode', 'No. Kontrak', 'No. CIF', 'Nama Nasabah', 'Kode Kantor',
            'Kode Akad', 'Kode Produk', 'Guna Debitur', 'Segment Key',
            'Tipe CKPN', 'In Scope PSAK 414', 'Bucket', 'EAD',
            'PD Netflow', 'LGD (%)', 'CKPN PSAK 414', 'PPKA Wajib',
            'Selisih', 'Cadangan Tambahan',
        ];
    }
}
