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
 * Dompet saldo prabayar: top-up & bayar order memakai saldo.
 */
class WalletTest extends TestCase
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
            'user_id' => $this->member->id, 'nama' => 'Budi', 'no_hp' => '081234567890', 'poin' => 0, 'saldo' => 0,
        ]);
        $this->service = Service::create([
            'user_id' => $this->member->id, 'nama' => 'Cuci Kiloan', 'satuan' => 'kg',
            'tarif' => 10000, 'kategori' => 'laundry', 'aktif' => true,
        ]);

        $this->actingAs($this->member);
    }

    private function buatOrderBelum(): Order
    {
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'belum',
            'items' => [['service_id' => $this->service->id, 'qty' => 3]], // 30.000
        ])->assertSessionHasNoErrors();

        return Order::firstOrFail();
    }

    public function test_topup_menambah_saldo_dan_ledger(): void
    {
        $this->post(route('customers.topup', $this->customer), ['jumlah' => 50000, 'metode' => 'cash'])
            ->assertSessionHasNoErrors();

        $this->assertSame(50000, (int) $this->customer->fresh()->saldo);
        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $this->customer->id, 'type' => 'topup', 'amount' => 50000,
        ]);
    }

    public function test_bayar_order_pakai_saldo(): void
    {
        $this->post(route('customers.topup', $this->customer), ['jumlah' => 50000]);
        $order = $this->buatOrderBelum();

        $this->post(route('orders.payment', $order), ['jumlah_bayar' => 30000, 'metode_bayar' => 'saldo'])
            ->assertSessionHasNoErrors();

        $this->assertSame('lunas', $order->fresh()->status_bayar);
        $this->assertSame(20000, (int) $this->customer->fresh()->saldo);       // 50.000 - 30.000
        $this->assertSame(3, (int) $this->customer->fresh()->poin);            // earn dari 30.000
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'metode' => 'saldo', 'jumlah' => 30000]);
        $this->assertDatabaseHas('wallet_transactions', ['order_id' => $order->id, 'type' => 'payment', 'amount' => -30000]);
    }

    public function test_bayar_saldo_ditolak_jika_kurang(): void
    {
        $order = $this->buatOrderBelum(); // saldo masih 0

        $this->post(route('orders.payment', $order), ['jumlah_bayar' => 30000, 'metode_bayar' => 'saldo'])
            ->assertSessionHas('error');

        $this->assertSame('belum', $order->fresh()->status_bayar);
        $this->assertDatabaseMissing('payments', ['order_id' => $order->id, 'metode' => 'saldo']);
    }

    public function test_halaman_saldo_render(): void
    {
        $this->post(route('customers.topup', $this->customer), ['jumlah' => 25000]);

        $this->get(route('customers.wallet', $this->customer))
            ->assertOk()
            ->assertSee('Saldo Dompet')
            ->assertSee('Top-up');
    }
}
