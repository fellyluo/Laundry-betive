<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat (ledger) dompet saldo prabayar pelanggan.
 * - type 'topup'   : pengisian saldo (amount > 0)
 * - type 'payment' : saldo dipakai bayar order (amount < 0)
 * - type 'refund'  : pengembalian saldo (amount > 0)
 * - type 'adjust'  : penyesuaian manual
 */
class WalletTransaction extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'customer_id', 'order_id', 'type', 'amount', 'metode', 'note'];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
