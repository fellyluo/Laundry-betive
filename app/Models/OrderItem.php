<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'service_id', 'qty', 'harga_satuan', 'subtotal'];

    protected $casts = [
        'qty' => 'float',
        'harga_satuan' => 'integer',
        'subtotal' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
