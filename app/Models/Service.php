<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['nama', 'satuan', 'tarif', 'kategori', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
        'tarif' => 'integer',
    ];
}
