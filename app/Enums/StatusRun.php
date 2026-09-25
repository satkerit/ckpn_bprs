<?php

namespace App\Enums;

/**
 * Status proses perhitungan CKPN.
 */
enum StatusRun: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Menunggu',
            self::Running => 'Berjalan',
            self::Done => 'Selesai',
            self::Failed => 'Gagal',
        };
    }
}
