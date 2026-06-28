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

    public function test_tukar_poin_jadi_potongan_order(): void
    {
        // Order pertama lunas (200.000) -> 20 poin.
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'lunas',
            'items' => [['service_id' => $this->service->id, 'qty' => 20]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(20, (int) $this->customer->fresh()->poin);

        // Order kedua belum bayar (30.000).
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'belum',
            'items' => [['service_id' => $this->service->id, 'qty' => 3]],
        ])->assertSessionHasNoErrors();
        $order = Order::orderByDesc('id')->firstOrFail();

        // Tukar 10 poin -> potongan 10.000 (poin_value default 1.000).
        $this->post(route('orders.redeem', $order), ['poin' => 10])->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertSame(10000, (int) $order->diskon_poin);
        $this->assertSame(10, (int) $order->poin_redeemed);
        $this->assertSame(10, (int) $this->customer->fresh()->poin); // 20 - 10

        // Bayar sisa 20.000 -> lunas + earn 2 poin (dari net 20.000).
        $this->post(route('orders.payment', $order), ['jumlah_bayar' => 20000, 'metode_bayar' => 'cash'])
            ->assertSessionHasNoErrors();
        $this->assertSame('lunas', $order->fresh()->status_bayar);
        $this->assertSame(12, (int) $this->customer->fresh()->poin); // 10 + 2
    }

    public function test_redeem_ditolak_jika_poin_tidak_cukup(): void
    {
        // Pelanggan punya 0 poin -> penukaran ditolak, tidak ada potongan.
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'belum',
            'items' => [['service_id' => $this->service->id, 'qty' => 3]],
        ])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();

        $this->post(route('orders.redeem', $order), ['poin' => 10])->assertSessionHas('error');
        $this->assertSame(0, (int) $order->fresh()->diskon_poin);
    }

    public function test_pembatalan_order_lunas_menarik_kembali_poin(): void
    {
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'lunas',
            'items' => [['service_id' => $this->service->id, 'qty' => 4]], // 40.000 -> 4 poin
        ])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame(4, (int) $this->customer->fresh()->poin);

        // Batalkan -> poin yang sempat diberikan ditarik kembali.
        $this->post(route('orders.status', $order), ['status' => 'dibatalkan'])->assertSessionHasNoErrors();
        $this->assertSame('dibatalkan', $order->fresh()->status);
        $this->assertSame(0, (int) $this->customer->fresh()->poin);
    }

    public function test_halaman_riwayat_poin_menampilkan_ledger(): void
    {
        // Order lunas -> tercatat entri ledger 'earn'.
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'lunas',
            'items' => [['service_id' => $this->service->id, 'qty' => 3]],
        ])->assertSessionHasNoErrors();

        $res = $this->get(route('customers.points', $this->customer));
        $res->assertOk();
        $res->assertSee('Riwayat Mutasi Poin');
        $res->assertSee('Dapat Poin');
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
