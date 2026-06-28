<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Membuat model ter-isolasi per member:
 * - Saat member login: semua query otomatis difilter ke data miliknya (user_id).
 * - Saat membuat record: user_id otomatis di-set ke member yang login.
 * - Super admin / guest: tidak difilter (untuk monitoring & form publik QR yang set user_id manual).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = Auth::user();
            if ($user && $user->role === 'member') {
                $builder->where($builder->getModel()->getTable().'.user_id', $user->id);
            }
        });

        static::creating(function ($model) {
            $user = Auth::user();
            if (empty($model->user_id) && $user && $user->role === 'member') {
                $model->user_id = $user->id;
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
