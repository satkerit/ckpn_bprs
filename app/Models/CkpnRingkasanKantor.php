<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnRingkasanKantor extends Model
{
    protected $table = 'ckpn_ringkasan_kantor';

    protected $fillable = ['ckpn_run_id', 'periode', 'kdloc', 'jumlah_rekening', 'total_ead', 'total_ckpn', 'total_ppka'];

    protected function casts(): array
    {
        return ['jumlah_rekening' => 'integer', 'total_ead' => 'decimal:2', 'total_ckpn' => 'decimal:2', 'total_ppka' => 'decimal:2'];
    }

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'kdloc', 'kdloc');
    }
}
