<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Service extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'nama', 'satuan', 'tarif', 'kategori', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
        'tarif' => 'integer',
    ];
}
