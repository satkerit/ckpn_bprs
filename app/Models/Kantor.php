<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kantor extends Model
{
    protected $table = 'kantor';

    public $incrementing = false;

    protected $primaryKey = 'kdloc';

    protected $keyType = 'string';

    protected $fillable = ['kdloc', 'nama', 'alamat'];

    public function pembiayaan(): HasMany
    {
        return $this->hasMany(Pembiayaan::class, 'kdloc', 'kdloc');
    }
}
