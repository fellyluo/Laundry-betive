<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Super Admin (pemilik aplikasi)
        User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'password' => Hash::make('admin123'), 'role' => 'super_admin', 'is_active' => true]
        );

        // Member demo (pemilik data laundry contoh)
        $demo = User::firstOrCreate(
            ['username' => 'demo'],
            ['name' => 'Laundry Demo', 'password' => Hash::make('demo123'), 'role' => 'member', 'is_active' => true]
        );

        // Settings platform (untuk landing/super admin) + settings member demo
        Setting::withoutGlobalScopes()->firstOrCreate(['user_id' => null], ['value' => Settings::defaults()]);
        Setting::withoutGlobalScopes()->firstOrCreate(['user_id' => $demo->id], ['value' => Settings::defaults()]);

        $services = [
            ['nama' => 'Cuci Setrika (Kg)',       'satuan' => 'kg',  'tarif' => 8000,  'kategori' => 'laundry', 'aktif' => true],
            ['nama' => 'Cuci Kering Saja (Kg)',   'satuan' => 'kg',  'tarif' => 6000,  'kategori' => 'laundry', 'aktif' => true],
            ['nama' => 'Setrika Saja (Kg)',       'satuan' => 'kg',  'tarif' => 5000,  'kategori' => 'laundry', 'aktif' => true],
            ['nama' => 'Cuci Selimut (Pcs)',      'satuan' => 'pcs', 'tarif' => 20000, 'kategori' => 'laundry', 'aktif' => true],
            ['nama' => 'Dry Clean Jas (Pcs)',     'satuan' => 'pcs', 'tarif' => 45000, 'kategori' => 'laundry', 'aktif' => true],
            ['nama' => 'Cuci Sepatu (Pcs)',       'satuan' => 'pcs', 'tarif' => 30000, 'kategori' => 'laundry', 'aktif' => true],
            ['nama' => 'Penjualan Sabun (Pcs)',   'satuan' => 'pcs', 'tarif' => 5000,  'kategori' => 'sabun',   'aktif' => true],
        ];
        foreach ($services as $s) {
            Service::withoutGlobalScopes()->firstOrCreate(['user_id' => $demo->id, 'nama' => $s['nama']], $s + ['user_id' => $demo->id]);
        }

        $customers = [
            ['nama' => 'Budi Santoso', 'no_hp' => '081234567890', 'alamat' => 'Jl. Merdeka No. 12, Jakarta', 'poin' => 15],
            ['nama' => 'Siti Aminah',  'no_hp' => '082198765432', 'alamat' => 'Perum Cempaka Indah B-5, Jakarta', 'poin' => 8],
            ['nama' => 'Agus Wijaya',  'no_hp' => '085711223344', 'alamat' => 'Kost Jaya Kamar 10, Jakarta', 'poin' => 0],
        ];
        foreach ($customers as $c) {
            Customer::withoutGlobalScopes()->firstOrCreate(['user_id' => $demo->id, 'no_hp' => $c['no_hp']], $c + ['user_id' => $demo->id]);
        }
    }
}
