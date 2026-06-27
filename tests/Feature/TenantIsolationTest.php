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
 * Memastikan data tiap member laundry benar-benar terisolasi:
 * member tidak boleh melihat, membuka, atau memakai data milik member lain.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $username): User
    {
        return User::create([
            'name' => $username,
            'username' => $username,
            'password' => Hash::make('secret123'),
            'role' => 'member',
            'is_active' => true,
        ]);
    }

    private function customerFor(User $u, string $nama): Customer
    {
        // Dibuat sebagai guest + user_id eksplisit -> tidak terpengaruh global scope acting user.
        return Customer::create([
            'user_id' => $u->id,
            'nama' => $nama,
            'no_hp' => '08' . str_pad((string) $u->id, 9, '0', STR_PAD_LEFT),
            'poin' => 0,
        ]);
    }

    private function serviceFor(User $u, string $nama): Service
    {
        return Service::create([
            'user_id' => $u->id,
            'nama' => $nama,
            'satuan' => 'kg',
            'tarif' => 10000,
            'kategori' => 'laundry',
            'aktif' => true,
        ]);
    }

    private function orderFor(User $u, Customer $c, string $nota): Order
    {
        return Order::create([
            'user_id' => $u->id,
            'nomor_nota' => $nota,
            'customer_id' => $c->id,
            'tanggal_masuk' => now(),
            'estimasi_selesai' => now()->addDay(),
            'status' => 'diterima',
            'total' => 10000,
            'status_bayar' => 'belum',
        ]);
    }

    /** Global scope: member hanya "melihat" data miliknya sendiri lewat Eloquent. */
    public function test_global_scope_filters_records_per_member(): void
    {
        $a = $this->member('laundry_a');
        $b = $this->member('laundry_b');
        $this->customerFor($a, 'Pelanggan A');
        $this->customerFor($b, 'Pelanggan B');

        $this->actingAs($a);
        $this->assertSame(1, Customer::count());
        $this->assertSame('Pelanggan A', Customer::first()->nama);

        $this->actingAs($b);
        $this->assertSame(1, Customer::count());
        $this->assertSame('Pelanggan B', Customer::first()->nama);
    }

    /** Route model binding ikut ter-scope: member tak bisa membuka order tenant lain (404). */
    public function test_member_cannot_open_other_tenant_order(): void
    {
        $a = $this->member('laundry_a');
        $b = $this->member('laundry_b');
        $custB = $this->customerFor($b, 'Pelanggan B');
        $orderB = $this->orderFor($b, $custB, '20240101-001');

        $this->actingAs($a)
            ->get(route('orders.show', $orderB))
            ->assertNotFound();
    }

    /** Aturan exists yang sudah di-scope: tolak customer_id milik tenant lain. */
    public function test_member_cannot_create_order_with_other_tenant_customer(): void
    {
        $a = $this->member('laundry_a');
        $b = $this->member('laundry_b');
        $svcA = $this->serviceFor($a, 'Cuci Kiloan A');
        $custB = $this->customerFor($b, 'Pelanggan B');

        $this->actingAs($a)
            ->post(route('orders.store'), [
                'customer_id' => $custB->id, // milik member lain
                'estimasi_selesai' => now()->addDay()->toDateString(),
                'status_bayar' => 'belum',
                'items' => [['service_id' => $svcA->id, 'qty' => 2]],
            ])
            ->assertSessionHasErrors('customer_id');

        $this->actingAs($a);
        $this->assertSame(0, Order::count());
    }

    /** Aturan exists yang sudah di-scope: tolak service_id milik tenant lain. */
    public function test_member_cannot_create_order_with_other_tenant_service(): void
    {
        $a = $this->member('laundry_a');
        $b = $this->member('laundry_b');
        $custA = $this->customerFor($a, 'Pelanggan A');
        $svcB = $this->serviceFor($b, 'Cuci Kiloan B');

        $this->actingAs($a)
            ->post(route('orders.store'), [
                'customer_id' => $custA->id,
                'estimasi_selesai' => now()->addDay()->toDateString(),
                'status_bayar' => 'belum',
                'items' => [['service_id' => $svcB->id, 'qty' => 2]],
            ])
            ->assertSessionHasErrors('items.0.service_id');

        $this->actingAs($a);
        $this->assertSame(0, Order::count());
    }

    /** Jalur normal tetap jalan: member bisa membuat order dengan data miliknya sendiri. */
    public function test_member_can_create_order_with_own_data(): void
    {
        $a = $this->member('laundry_a');
        $custA = $this->customerFor($a, 'Pelanggan A');
        $svcA = $this->serviceFor($a, 'Cuci Kiloan A');

        $this->actingAs($a)
            ->post(route('orders.store'), [
                'customer_id' => $custA->id,
                'estimasi_selesai' => now()->addDay()->toDateString(),
                'status_bayar' => 'belum',
                'items' => [['service_id' => $svcA->id, 'qty' => 2]],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($a);
        $this->assertSame(1, Order::count());
        $this->assertSame(20000, (int) Order::first()->total); // 2 kg x 10.000
    }
}
