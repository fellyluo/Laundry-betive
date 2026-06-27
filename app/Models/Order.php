<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Order extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'nomor_nota', 'customer_id', 'tanggal_masuk', 'estimasi_selesai',
        'status', 'total', 'status_bayar', 'poin_awarded', 'catatan',
    ];

    protected $casts = [
        'tanggal_masuk' => 'datetime',
        'estimasi_selesai' => 'datetime',
        'total' => 'integer',
        'poin_awarded' => 'boolean',
    ];

    /**
     * Selaraskan status bayar dengan total pembayaran, dan beri poin loyalitas
     * (1 poin / Rp 10.000 dari total order) tepat saat order pertama kali LUNAS.
     * Idempotent: poin tidak diberikan dua kali untuk order yang sama.
     */
    public function syncPaymentStatus(): void
    {
        $paid = (int) $this->payments()->sum('jumlah');
        $lunas = $this->total > 0 && $paid >= $this->total;

        $this->status_bayar = $lunas ? 'lunas' : ($paid > 0 ? 'dp' : 'belum');

        if ($lunas && ! $this->poin_awarded) {
            $points = intdiv((int) $this->total, 10000);
            if ($points > 0) {
                $customer = $this->customer()->withoutGlobalScopes()->first();
                if ($customer) {
                    $customer->increment('poin', $points);
                }
            }
            $this->poin_awarded = true;
        }

        $this->save();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function logs()
    {
        return $this->hasMany(StatusLog::class)->orderBy('created_at');
    }
}
