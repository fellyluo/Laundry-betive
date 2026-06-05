<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['nama', 'no_hp', 'alamat', 'poin', 'metode_bayar', 'via_qr'];

    protected $casts = [
        'via_qr' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
