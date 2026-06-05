<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['tanggal', 'keterangan', 'kategori', 'jumlah'];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'integer',
    ];
}
