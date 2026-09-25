<?php

namespace App\Models;

use App\Enums\SyaratMasukCkpn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Role penentuan CKPN per kode akad (pokpby): kolom history mana yang
 * membentuk EAD, dan syarat kapan rekening masuk populasi perhitungan.
 */
class AkadRole extends Model
{
    protected $table = 'ckpn_akad_role';

    public $incrementing = false;

    protected $primaryKey = 'pokpby';

    protected $keyType = 'string';

    protected $fillable = [
        'pokpby', 'ead_osmdlc', 'ead_osmgnc', 'ead_tgkmdl', 'ead_tgkmgn',
        'syarat_masuk',
    ];

    protected function casts(): array
    {
        return [
            'ead_osmdlc' => 'boolean',
            'ead_osmgnc' => 'boolean',
            'ead_tgkmdl' => 'boolean',
            'ead_tgkmgn' => 'boolean',
            'syarat_masuk' => SyaratMasukCkpn::class,
        ];
    }

    public function akad(): BelongsTo
    {
        return $this->belongsTo(KodeAkad::class, 'pokpby', 'pokpby');
    }

    /** Daftar kolom history pembiayaan yang membentuk EAD untuk role ini. */
    public function eadColumns(): array
    {
        $columns = [];

        foreach (['ead_osmdlc' => 'osmdlc', 'ead_osmgnc' => 'osmgnc', 'ead_tgkmdl' => 'tgkmdl', 'ead_tgkmgn' => 'tgkmgn'] as $flag => $column) {
            if ($this->{$flag}) {
                $columns[] = $column;
            }
        }

        return $columns;
    }
}
