<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Poin loyalitas: 1 poin / Rp 10.000, diberikan saat order LUNAS (bukan saat dibuat),
 * dan tidak boleh dobel untuk order yang sama.
 */
class LoyaltyPointsTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Customer $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::create([
            'name' => 'laundry_a', 'username' => 'laundry_a',
            'password' => Hash::make('secret123'), 'role' => 'member', 'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'user_id' => $this->member->id, 'nama' => 'Budi', 'no_hp' => '081234567890', 'poin' => 0,
        ]);
        $this->service = Service::create([
            'user_id' => $this->member->id, 'nama' => 'Cuci Kiloan', 'satuan' => 'kg',
            'tarif' => 10000, 'kategori' => 'laundry', 'aktif' => true,
        ]);

        $this->actingAs($this->member);
    }

    public function test_order_belum_bayar_tidak_memberi_poin(): void
    {
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'belum',
            'items' => [['service_id' => $this->service->id, 'qty' => 3]], // total 30.000
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, (int) $this->customer->fresh()->poin);
    }

    public function test_order_lunas_langsung_memberi_poin(): void
    {
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'lunas',
            'items' => [['service_id' => $this->service->id, 'qty' => 3]], // total 30.000 -> 3 poin
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, (int) $this->customer->fresh()->poin);
    }

    public function test_pelunasan_via_pembayaran_memberi_poin_sekali(): void
    {
        // Order belum bayar dulu (total 30.000).
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'belum',
            'items' => [['service_id' => $this->service->id, 'qty' => 3]],
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(0, (int) $this->customer->fresh()->poin);

        // Bayar lunas -> dapat 3 poin.
        $this->post(route('orders.payment', $order), [
            'jumlah_bayar' => 30000, 'metode_bayar' => 'cash',
        ])->assertSessionHasNoErrors();
        $this->assertSame('lunas', $order->fresh()->status_bayar);
        $this->assertSame(3, (int) $this->customer->fresh()->poin);

        // Pembayaran tambahan (lebih bayar) tidak menambah poin lagi.
        $this->post(route('orders.payment', $order), [
            'jumlah_bayar' => 5000, 'metode_bayar' => 'cash',
        ])->assertSessionHasNoErrors();
        $this->assertSame(3, (int) $this->customer->fresh()->poin);
    }

    public function test_pembayaran_sebagian_jadi_dp_tanpa_poin(): void
    {
        // Order belum bayar, total 30.000.
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'belum',
            'items' => [['service_id' => $this->service->id, 'qty' => 3]],
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();

        // Bayar sebagian (DP) -> status 'dp', belum dapat poin.
        $this->post(route('orders.payment', $order), ['jumlah_bayar' => 10000, 'metode_bayar' => 'cash']);
        $this->assertSame('dp', $order->fresh()->status_bayar);
        $this->assertSame(0, (int) $this->customer->fresh()->poin);

        // Lunasi sisanya -> status 'lunas' + 3 poin.
        $this->post(route('orders.payment', $order), ['jumlah_bayar' => 20000, 'metode_bayar' => 'cash']);
        $this->assertSame('lunas', $order->fresh()->status_bayar);
        $this->assertSame(3, (int) $this->customer->fresh()->poin);
    }

    public function test_dashboard_member_tampil_tanpa_error(): void
    {
        // Satu order lunas agar agregasi dashboard mengeksekusi semua cabang query.
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'lunas',
            'items' => [['service_id' => $this->service->id, 'qty' => 2]],
        ])->assertSessionHasNoErrors();

        $this->get(route('dashboard'))->assertOk();
    }
}
