<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'nomor_nota', 'public_token', 'customer_id', 'tanggal_masuk', 'estimasi_selesai',
        'status', 'total', 'status_bayar', 'poin_awarded', 'poin_redeemed', 'diskon_poin',
        'diskon', 'voucher_code', 'catatan',
    ];

    protected $casts = [
        'tanggal_masuk' => 'datetime',
        'estimasi_selesai' => 'datetime',
        'total' => 'integer',
        'poin_awarded' => 'boolean',
        'poin_redeemed' => 'integer',
        'diskon_poin' => 'integer',
        'diskon' => 'integer',
    ];

    /** Buat token publik unik untuk halaman lacak status pelanggan. */
    public static function generatePublicToken(): string
    {
        do {
            $token = Str::lower(Str::random(20));
        } while (static::withoutGlobalScopes()->where('public_token', $token)->exists());

        return $token;
    }

    /** Tagihan bersih = total dikurangi potongan poin & diskon/voucher (tidak pernah negatif). */
    public function netTotal(): int
    {
        return max(0, (int) $this->total - (int) $this->diskon_poin - (int) $this->diskon);
    }

    /**
     * Selaraskan status bayar dengan total pembayaran, dan beri poin loyalitas
     * (rate dari pengaturan member, default 1 poin / Rp 10.000) tepat saat order
     * pertama kali LUNAS. Idempotent: poin tidak diberikan dua kali untuk order yang sama.
     * Poin dihitung dari tagihan bersih (setelah potongan poin).
     */
    public function syncPaymentStatus(): void
    {
        $paid = (int) $this->payments()->sum('jumlah');
        $net = $this->netTotal();
        $lunas = $this->total > 0 && $paid >= $net;

        $this->status_bayar = $lunas ? 'lunas' : ($paid > 0 ? 'dp' : 'belum');

        $loyalty = Settings::loyalty($this->user_id);
        if ($lunas && ! $this->poin_awarded && $loyalty['enabled']) {
            $rate = $loyalty['earn_rate'];
            $points = $rate > 0 ? intdiv($net, $rate) : 0;
            if ($points > 0) {
                $customer = $this->customer()->withoutGlobalScopes()->first();
                if ($customer) {
                    $customer->increment('poin', $points);
                    $this->pointTransactions()->create([
                        'user_id' => $this->user_id,
                        'customer_id' => $this->customer_id,
                        'type' => 'earn',
                        'points' => $points,
                        'note' => 'Poin dari order '.$this->nomor_nota,
                    ]);
                }
            }
            $this->poin_awarded = true;
        }

        $this->save();
    }

    /**
     * Balikkan seluruh mutasi poin order ini saat dibatalkan: poin yang sempat
     * diberikan ditarik kembali, poin yang sempat ditukar dikembalikan ke pelanggan.
     */
    public function reverseLoyaltyOnCancel(): void
    {
        $net = (int) $this->pointTransactions()->sum('points'); // mutasi bersih yang sudah diterapkan ke saldo
        if ($net !== 0) {
            $customer = $this->customer()->withoutGlobalScopes()->first();
            if ($customer) {
                $customer->update(['poin' => max(0, (int) $customer->poin - $net)]);
                $this->pointTransactions()->create([
                    'user_id' => $this->user_id,
                    'customer_id' => $this->customer_id,
                    'type' => 'reversal',
                    'points' => -$net,
                    'note' => 'Pembatalan order '.$this->nomor_nota,
                ]);
            }
        }

        $this->poin_awarded = false;
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

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }
}
