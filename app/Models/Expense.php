<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Expense extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'tanggal', 'keterangan', 'kategori', 'jumlah'];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'integer',
    ];
}
