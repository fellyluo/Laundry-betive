<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'nama', 'no_hp', 'alamat', 'poin', 'saldo', 'metode_bayar', 'via_qr'];

    protected $casts = [
        'via_qr' => 'boolean',
        'poin' => 'integer',
        'saldo' => 'integer',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
