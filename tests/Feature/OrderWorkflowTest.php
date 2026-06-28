<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::create([
            'name' => 'laundry_a', 'username' => 'laundry_a',
            'password' => Hash::make('secret123'), 'role' => 'member', 'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'user_id' => $this->member->id, 'nama' => 'Andi', 'no_hp' => '081200000001', 'poin' => 0,
        ]);
        $this->actingAs($this->member);
    }

    private function makeOrder(string $status = 'diterima', string $nota = '20240101-001'): Order
    {
        return Order::create([
            'user_id' => $this->member->id, 'nomor_nota' => $nota, 'customer_id' => $this->customer->id,
            'tanggal_masuk' => now(), 'estimasi_selesai' => now()->addDay(),
            'status' => $status, 'total' => 10000, 'status_bayar' => 'belum',
        ]);
    }

    // ---- #10 Validasi transisi status ----

    public function test_transisi_status_valid_diterima_ke_diproses(): void
    {
        $order = $this->makeOrder('diterima');

        $this->post(route('orders.status', $order), ['status' => 'diproses']);

        $this->assertSame('diproses', $order->fresh()->status);
        $this->assertDatabaseHas('status_logs', ['order_id' => $order->id, 'status' => 'diproses']);
    }

    public function test_transisi_status_meloncat_ditolak(): void
    {
        $order = $this->makeOrder('diterima');

        $this->post(route('orders.status', $order), ['status' => 'diambil'])
            ->assertSessionHas('error');

        $this->assertSame('diterima', $order->fresh()->status);
    }

    public function test_status_final_tidak_bisa_diubah(): void
    {
        $order = $this->makeOrder('dibatalkan');

        $this->post(route('orders.status', $order), ['status' => 'diproses'])
            ->assertSessionHas('error');

        $this->assertSame('dibatalkan', $order->fresh()->status);
    }

    // ---- #5 Guard hapus pelanggan ----

    public function test_pelanggan_dengan_order_tidak_bisa_dihapus(): void
    {
        $this->makeOrder('diterima');

        $this->delete(route('customers.destroy', $this->customer))->assertSessionHas('error');

        $this->assertDatabaseHas('customers', ['id' => $this->customer->id]);
    }

    public function test_pelanggan_tanpa_order_bisa_dihapus(): void
    {
        $lain = Customer::create(['user_id' => $this->member->id, 'nama' => 'Budi', 'no_hp' => '081200000002', 'poin' => 0]);

        $this->delete(route('customers.destroy', $lain));

        $this->assertDatabaseMissing('customers', ['id' => $lain->id]);
    }

    // ---- #9 Pencarian server-side di daftar order ----

    public function test_pencarian_order_memfilter_hasil(): void
    {
        $this->makeOrder('diterima', '20240101-001'); // pelanggan: Andi
        $budi = Customer::create(['user_id' => $this->member->id, 'nama' => 'Budi', 'no_hp' => '081200000099', 'poin' => 0]);
        Order::create([
            'user_id' => $this->member->id, 'nomor_nota' => '20240101-002', 'customer_id' => $budi->id,
            'tanggal_masuk' => now(), 'estimasi_selesai' => now()->addDay(),
            'status' => 'diterima', 'total' => 10000, 'status_bayar' => 'belum',
        ]);

        $this->get(route('orders.index', ['q' => 'Andi']))
            ->assertOk()
            ->assertSee('20240101-001')
            ->assertDontSee('20240101-002');
    }
}
