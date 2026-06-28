<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pelacakan status order oleh pelanggan (publik, tanpa login).
 */
class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $member = User::create([
            'name' => 'laundry_a', 'username' => 'laundry_a',
            'password' => Hash::make('secret123'), 'role' => 'member', 'is_active' => true,
        ]);
        $customer = Customer::create([
            'user_id' => $member->id, 'nama' => 'Budi', 'no_hp' => '081234567890', 'poin' => 0,
        ]);
        $this->order = Order::create([
            'user_id' => $member->id, 'nomor_nota' => '20260628-001', 'public_token' => 'tokenrahasia123',
            'customer_id' => $customer->id, 'tanggal_masuk' => now(), 'estimasi_selesai' => now()->addDay(),
            'status' => 'diproses', 'total' => 30000, 'status_bayar' => 'belum',
        ]);
        $this->order->logs()->create(['status' => 'diterima']);
    }

    public function test_halaman_status_tampil_via_token(): void
    {
        $this->get(route('track.show', 'tokenrahasia123'))
            ->assertOk()
            ->assertSee('20260628-001')
            ->assertSee('Sedang Dicuci');
    }

    public function test_token_tidak_valid_404(): void
    {
        $this->get(route('track.show', 'tokensalah'))->assertNotFound();
    }

    public function test_cari_dengan_nota_dan_hp_benar(): void
    {
        $this->post(route('track.find'), [
            'nomor_nota' => '20260628-001',
            'no_hp' => '081234567890',
        ])->assertRedirect(route('track.show', 'tokenrahasia123'));
    }

    public function test_cari_dengan_hp_salah_ditolak(): void
    {
        $this->post(route('track.find'), [
            'nomor_nota' => '20260628-001',
            'no_hp' => '089999999999',
        ])->assertSessionHas('error');
    }
}
