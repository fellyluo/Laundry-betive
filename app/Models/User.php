<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'username',
        'password',
        'role',
        'is_active',
        'subscribed_until',
        'plan',
        'plan_price',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_until' => 'date',
        'plan_price' => 'integer',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /** Langganan kedaluwarsa (jika ada tanggalnya dan sudah lewat). */
    public function subscriptionExpired(): bool
    {
        return $this->subscribed_until !== null
            && $this->subscribed_until->lt(Carbon::today());
    }

    /** Terblokir: member yang di-suspend atau masa sewanya habis. Super admin tidak pernah terblokir. */
    public function isBlocked(): bool
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        return ! $this->is_active || $this->subscriptionExpired();
    }

    /** Sisa hari sewa (null = tanpa batas, negatif = sudah lewat). */
    public function daysLeft(): ?int
    {
        if ($this->subscribed_until === null) {
            return null;
        }

        return Carbon::today()->diffInDays($this->subscribed_until, false);
    }
}
