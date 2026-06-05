<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id', 'jumlah', 'metode'];

    protected $casts = [
        'jumlah' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
