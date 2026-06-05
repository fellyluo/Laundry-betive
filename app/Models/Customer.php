<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'nama', 'no_hp', 'alamat', 'poin', 'metode_bayar', 'via_qr'];

    protected $casts = [
        'via_qr' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
