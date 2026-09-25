<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisPenggunaanRef extends Model
{
    protected $table = 'jenis_penggunaan';

    public $incrementing = false;

    protected $primaryKey = 'kode';

    protected $keyType = 'string';

    protected $fillable = ['kode', 'nama'];
}
