<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat (ledger) poin loyalitas pelanggan.
 * - type 'earn'     : poin didapat saat order lunas (points > 0)
 * - type 'redeem'   : poin ditukar jadi potongan order (points < 0)
 * - type 'reversal' : pembalik saat order dibatalkan
 * - type 'adjust'   : penyesuaian manual oleh member
 */
class PointTransaction extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'customer_id', 'order_id', 'type', 'points', 'note'];

    protected $casts = [
        'points' => 'integer',
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
