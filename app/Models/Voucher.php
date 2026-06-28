<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Voucher extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'kode', 'tipe', 'nilai', 'min_belanja', 'kuota', 'terpakai', 'aktif', 'kadaluarsa'];

    protected $casts = [
        'nilai' => 'integer',
        'min_belanja' => 'integer',
        'kuota' => 'integer',
        'terpakai' => 'integer',
        'aktif' => 'boolean',
        'kadaluarsa' => 'date',
    ];

    public function habis(): bool
    {
        return $this->kuota !== null && $this->terpakai >= $this->kuota;
    }

    public function kadaluarsaLewat(): bool
    {
        return $this->kadaluarsa !== null && $this->kadaluarsa->lt(Carbon::today());
    }

    public function bisaDipakai(): bool
    {
        return $this->aktif && ! $this->habis() && ! $this->kadaluarsaLewat();
    }

    /** Hitung potongan (Rp) atas dasar belanja $base. */
    public function hitungDiskon(int $base): int
    {
        if ($this->tipe === 'persen') {
            return (int) round($base * min(100, max(0, $this->nilai)) / 100);
        }

        return min(max(0, $this->nilai), $base);
    }
}
