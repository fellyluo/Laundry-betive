<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Diskon manual & voucher pada order.
 */
class DiscountVoucherTest extends TestCase
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

    private function orderBelum(int $qty = 5): Order
    {
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'belum',
            'items' => [['service_id' => $this->service->id, 'qty' => $qty]],
        ])->assertSessionHasNoErrors();

        return Order::orderByDesc('id')->firstOrFail();
    }

    private function voucher(array $attr = []): Voucher
    {
        return Voucher::create(array_merge([
            'user_id' => $this->member->id, 'kode' => 'HEMAT10', 'tipe' => 'persen',
            'nilai' => 10, 'min_belanja' => 0, 'kuota' => null, 'terpakai' => 0, 'aktif' => true,
        ], $attr));
    }

    public function test_buat_voucher_dan_tolak_kode_duplikat(): void
    {
        $this->post(route('vouchers.store'), ['kode' => 'hemat10', 'tipe' => 'persen', 'nilai' => 10, 'aktif' => 1])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vouchers', ['kode' => 'HEMAT10', 'user_id' => $this->member->id]);

        // Kode sama (case-insensitive) harus ditolak.
        $this->post(route('vouchers.store'), ['kode' => 'Hemat10', 'tipe' => 'nominal', 'nilai' => 5000, 'aktif' => 1])
            ->assertSessionHasErrors('kode');
    }

    public function test_terapkan_voucher_persen(): void
    {
        $this->voucher(['tipe' => 'persen', 'nilai' => 10]);
        $order = $this->orderBelum(5); // 50.000

        $this->post(route('orders.voucher', $order), ['kode' => 'hemat10'])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(5000, (int) $order->diskon);       // 10% dari 50.000
        $this->assertSame('HEMAT10', $order->voucher_code);
        $this->assertSame(1, (int) Voucher::first()->terpakai);
        $this->assertSame(45000, $order->netTotal());
    }

    public function test_terapkan_diskon_nominal_manual(): void
    {
        $order = $this->orderBelum(5); // 50.000

        $this->post(route('orders.discount', $order), ['tipe' => 'nominal', 'nilai' => 8000])
            ->assertSessionHas('success');

        $this->assertSame(8000, (int) $order->fresh()->diskon);
        $this->assertNull($order->fresh()->voucher_code);
    }

    public function test_voucher_nonaktif_ditolak(): void
    {
        $this->voucher(['aktif' => false]);
        $order = $this->orderBelum(5);

        $this->post(route('orders.voucher', $order), ['kode' => 'HEMAT10'])->assertSessionHas('error');
        $this->assertSame(0, (int) $order->fresh()->diskon);
    }

    public function test_voucher_kuota_habis_ditolak(): void
    {
        $this->voucher(['kuota' => 1, 'terpakai' => 1]);
        $order = $this->orderBelum(5);

        $this->post(route('orders.voucher', $order), ['kode' => 'HEMAT10'])->assertSessionHas('error');
        $this->assertSame(0, (int) $order->fresh()->diskon);
    }

    public function test_hapus_diskon_mengembalikan_kuota_voucher(): void
    {
        $this->voucher(['kuota' => 5]);
        $order = $this->orderBelum(5);

        $this->post(route('orders.voucher', $order), ['kode' => 'HEMAT10']);
        $this->assertSame(1, (int) Voucher::first()->terpakai);

        $this->post(route('orders.discount.remove', $order))->assertSessionHas('success');
        $this->assertSame(0, (int) $order->fresh()->diskon);
        $this->assertNull($order->fresh()->voucher_code);
        $this->assertSame(0, (int) Voucher::first()->terpakai);
    }

    public function test_diskon_nonaktif_ditolak(): void
    {
        $s = Settings::get($this->member->id);
        $s['discount'] = ['enabled' => false];
        Settings::save($s, $this->member->id);

        $order = $this->orderBelum(5);

        $this->post(route('orders.discount', $order), ['tipe' => 'nominal', 'nilai' => 5000])
            ->assertSessionHas('error');
        $this->assertSame(0, (int) $order->fresh()->diskon);

        $this->voucher();
        $this->post(route('orders.voucher', $order), ['kode' => 'HEMAT10'])->assertSessionHas('error');
        $this->assertSame(0, (int) $order->fresh()->diskon);
    }

    public function test_batal_order_mengembalikan_kuota_voucher(): void
    {
        $this->voucher(['kuota' => 5]);
        $order = $this->orderBelum(5);
        $this->post(route('orders.voucher', $order), ['kode' => 'HEMAT10']);
        $this->assertSame(1, (int) Voucher::first()->terpakai);

        $this->post(route('orders.status', $order), ['status' => 'dibatalkan'])->assertSessionHasNoErrors();
        $this->assertSame(0, (int) Voucher::first()->terpakai);
    }
}
