<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'tanggal', 'keterangan', 'kategori', 'jumlah'];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'integer',
    ];
}
