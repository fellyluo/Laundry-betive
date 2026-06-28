<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Support\Settings;
use App\Support\WhatsappNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Notifikasi WhatsApp otomatis saat order berstatus "selesai".
 * Tidak ada panggilan jaringan nyata — gateway Fonnte di-fake.
 */
class WhatsappNotificationTest extends TestCase
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

    private function buatOrder(): Order
    {
        $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'estimasi_selesai' => now()->addDay()->toDateString(),
            'status_bayar' => 'lunas',
            'items' => [['service_id' => $this->service->id, 'qty' => 2]],
        ])->assertSessionHasNoErrors();

        return Order::firstOrFail();
    }

    private function aktifkanWa(): void
    {
        $s = Settings::get($this->member->id);
        $s['whatsapp'] = ['enabled' => true, 'token' => 'dummy-token', 'template_selesai' => 'Halo {nama}, nota {nota} sudah selesai di {laundry}.'];
        Settings::save($s, $this->member->id);
    }

    public function test_status_selesai_mengirim_wa_saat_aktif(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $this->aktifkanWa();

        $order = $this->buatOrder();
        $this->post(route('orders.status', $order), ['status' => 'diproses'])->assertSessionHasNoErrors();
        $this->post(route('orders.status', $order), ['status' => 'selesai'])->assertSessionHasNoErrors();

        Http::assertSent(fn ($req) => str_contains($req->url(), 'fonnte.com')
            && $req['target'] === '6281234567890'
            && str_contains($req['message'], 'selesai'));
    }

    public function test_status_selesai_tidak_mengirim_wa_saat_nonaktif(): void
    {
        Http::fake();

        $order = $this->buatOrder();
        $this->post(route('orders.status', $order), ['status' => 'diproses']);
        $this->post(route('orders.status', $order), ['status' => 'selesai'])->assertSessionHasNoErrors();

        Http::assertNothingSent();
    }

    public function test_normalisasi_nomor_hp(): void
    {
        $this->assertSame('6281234567890', WhatsappNotifier::normalizePhone('081234567890'));
        $this->assertSame('6281234567890', WhatsappNotifier::normalizePhone('+62 812-3456-7890'));
        $this->assertSame('6281234567890', WhatsappNotifier::normalizePhone('81234567890'));
        $this->assertSame('', WhatsappNotifier::normalizePhone('123'));
    }
}
