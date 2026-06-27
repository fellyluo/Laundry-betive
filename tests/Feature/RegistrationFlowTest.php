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
 * Alur pendaftaran pelanggan mandiri via QR (publik, tanpa login) — milik member laundry.
 */
class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $u): User
    {
        return User::create([
            'name' => $u, 'username' => $u,
            'password' => Hash::make('secret123'), 'role' => 'member', 'is_active' => true,
        ]);
    }

    private function service(User $u, string $nama, int $tarif = 10000): Service
    {
        return Service::create([
            'user_id' => $u->id, 'nama' => $nama, 'satuan' => 'kg',
            'tarif' => $tarif, 'kategori' => 'laundry', 'aktif' => true,
        ]);
    }

    public function test_form_qr_hanya_untuk_member(): void
    {
        $admin = User::create([
            'name' => 'admin', 'username' => 'admin',
            'password' => Hash::make('secret123'), 'role' => 'super_admin', 'is_active' => true,
        ]);

        $this->get(route('register.show', $admin))->assertNotFound();
    }

    public function test_form_qr_member_tampil(): void
    {
        $m = $this->member('laundry_a');
        $this->service($m, 'Cuci Kiloan');

        $this->get(route('register.show', $m))->assertOk();
    }

    public function test_pendaftaran_qr_membuat_pelanggan_milik_member(): void
    {
        $m = $this->member('laundry_a');

        $this->post(route('register.store', $m), [
            'nama' => 'Sinta', 'no_hp' => '081299990000',
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'user_id' => $m->id, 'nama' => 'Sinta', 'no_hp' => '081299990000', 'via_qr' => 1,
        ]);
    }

    public function test_pendaftaran_qr_dengan_layanan_membuat_order_tanpa_poin(): void
    {
        $m = $this->member('laundry_a');
        $svc = $this->service($m, 'Cuci Kiloan', 10000);

        $this->post(route('register.store', $m), [
            'nama' => 'Sinta', 'no_hp' => '081299990001',
            'items' => [['service_id' => $svc->id, 'qty' => 3]], // 3 x 10.000
        ])->assertOk();

        $cust = Customer::withoutGlobalScopes()->where('user_id', $m->id)->where('no_hp', '081299990001')->firstOrFail();
        $order = Order::withoutGlobalScopes()->where('user_id', $m->id)->firstOrFail();

        $this->assertSame(30000, (int) $order->total);
        $this->assertSame('belum', $order->status_bayar);
        $this->assertSame(0, (int) $cust->poin); // poin hanya saat lunas di outlet
    }

    public function test_pendaftaran_qr_mengabaikan_layanan_milik_member_lain(): void
    {
        $a = $this->member('laundry_a');
        $b = $this->member('laundry_b');
        $svcB = $this->service($b, 'Layanan B');

        // Pelanggan daftar ke laundry A tetapi mengirim service milik B.
        $this->post(route('register.store', $a), [
            'nama' => 'Sinta', 'no_hp' => '081299990002',
            'items' => [['service_id' => $svcB->id, 'qty' => 3]],
        ])->assertOk();

        // Service B difilter keluar -> tidak ada order untuk A.
        $this->assertSame(0, Order::withoutGlobalScopes()->where('user_id', $a->id)->count());
    }

    public function test_pendaftaran_qr_duplikat_hp_memperbarui_bukan_menggandakan(): void
    {
        $m = $this->member('laundry_a');
        Customer::create(['user_id' => $m->id, 'nama' => 'Lama', 'no_hp' => '081200001234', 'poin' => 0]);

        $this->post(route('register.store', $m), [
            'nama' => 'Baru', 'no_hp' => '081200001234',
        ])->assertOk();

        $this->assertSame(1, Customer::withoutGlobalScopes()
            ->where('user_id', $m->id)->where('no_hp', '081200001234')->count());
        $this->assertDatabaseHas('customers', ['user_id' => $m->id, 'no_hp' => '081200001234', 'nama' => 'Baru']);
    }
}
