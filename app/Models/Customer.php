<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['nama', 'no_hp', 'alamat', 'poin'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
