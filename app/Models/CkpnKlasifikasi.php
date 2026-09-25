<?php

namespace App\Models;

use App\Enums\TipeCkpn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CkpnKlasifikasi extends Model
{
    protected $table = 'ckpn_klasifikasi';

    protected $fillable = [
        'periode', 'nokontrak', 'tipe', 'alasan', 'metode_individual', 'nilai_individual', 'ditentukan_oleh',
    ];

    protected function casts(): array
    {
        return ['tipe' => TipeCkpn::class, 'nilai_individual' => 'decimal:2'];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditentukan_oleh');
    }
}
