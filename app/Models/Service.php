<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'nama', 'satuan', 'tarif', 'kategori', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
        'tarif' => 'integer',
    ];
}
