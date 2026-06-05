<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Order extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'nomor_nota', 'customer_id', 'tanggal_masuk', 'estimasi_selesai',
        'status', 'total', 'status_bayar', 'catatan',
    ];

    protected $casts = [
        'tanggal_masuk' => 'datetime',
        'estimasi_selesai' => 'datetime',
        'total' => 'integer',
    ];

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
