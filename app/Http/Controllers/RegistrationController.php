<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Service;
use App\Models\Order;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegistrationController extends Controller
{
    /** Form pendaftaran + order pelanggan, untuk laundry milik member $user (scan QR). */
    public function show(User $user)
    {
        abort_unless($user->role === 'member', 404);

        $settings = Settings::get($user->id);
        $methods = collect($settings['payment_methods'])->where('aktif', true)->values();
        $services = Service::withoutGlobalScopes()->where('user_id', $user->id)
            ->where('aktif', true)->orderBy('kategori')->orderBy('nama')->get();

        return view('register.form', ['member' => $user, 'methods' => $methods, 'services' => $services]);
    }

    public function store(Request $request, User $user)
    {
        abort_unless($user->role === 'member', 404);

        $settings = Settings::get($user->id);
        $activeNames = collect($settings['payment_methods'])->where('aktif', true)->pluck('nama')->all();

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => ['required', 'string', 'max:30', function ($attr, $value, $fail) {
                if (strlen(preg_replace('/[^0-9]/', '', $value)) < 8) {
                    $fail('Nomor HP tidak valid (minimal 8 angka)');
                }
            }],
            'alamat' => 'nullable|string',
            'metode_bayar' => ['nullable', 'string', function ($attr, $value, $fail) use ($activeNames) {
                if ($value && ! in_array($value, $activeNames)) {
                    $fail('Metode pembayaran tidak valid.');
                }
            }],
            'items' => 'nullable|array',
            'items.*.service_id' => 'required',
            'items.*.qty' => 'required|numeric|min:0.1',
        ], [
            'nama.required' => 'Nama wajib diisi',
            'no_hp.required' => 'Nomor HP wajib diisi',
        ]);

        // Customer milik member ini (cegah duplikat by HP dalam scope member)
        $customer = Customer::withoutGlobalScopes()->where('user_id', $user->id)
            ->where('no_hp', trim($validated['no_hp']))->first();
        $custData = [
            'nama' => trim($validated['nama']),
            'alamat' => isset($validated['alamat']) && trim($validated['alamat']) !== '' ? trim($validated['alamat']) : ($customer->alamat ?? null),
            'metode_bayar' => $validated['metode_bayar'] ?? ($customer->metode_bayar ?? null),
        ];
        if ($customer) {
            $customer->update($custData);
        } else {
            $customer = Customer::create($custData + ['user_id' => $user->id, 'no_hp' => trim($validated['no_hp']), 'poin' => 0, 'via_qr' => true]);
        }

        // Order (bila ada layanan dipilih) — hanya layanan milik member ini
        $order = null;
        $items = collect($validated['items'] ?? [])->filter(fn ($i) => ! empty($i['service_id']) && (float) ($i['qty'] ?? 0) > 0);
        if ($items->isNotEmpty()) {
            $order = DB::transaction(function () use ($items, $customer, $user) {
                $services = Service::withoutGlobalScopes()->where('user_id', $user->id)
                    ->whereIn('id', $items->pluck('service_id'))->get()->keyBy('id');
                $total = 0;
                $rows = [];
                foreach ($items as $row) {
                    $svc = $services[$row['service_id']] ?? null;
                    if (! $svc) {
                        continue;
                    }
                    $qty = (float) $row['qty'];
                    $harga = (int) $svc->tarif;
                    $sub = (int) round($qty * $harga);
                    $total += $sub;
                    $rows[] = ['service_id' => $svc->id, 'qty' => $qty, 'harga_satuan' => $harga, 'subtotal' => $sub];
                }
                if (empty($rows)) {
                    return null;
                }

                $prefix = Carbon::today()->format('Ymd');
                $seq = Order::withoutGlobalScopes()->whereDate('tanggal_masuk', Carbon::today())->count() + 1;
                do {
                    $nota = $prefix . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
                    $seq++;
                } while (Order::withoutGlobalScopes()->where('nomor_nota', $nota)->exists());

                $order = Order::create([
                    'user_id' => $user->id,
                    'nomor_nota' => $nota,
                    'customer_id' => $customer->id,
                    'tanggal_masuk' => now(),
                    'estimasi_selesai' => Carbon::now()->addDays(2)->setTime(17, 0),
                    'status' => 'diterima',
                    'total' => $total,
                    'status_bayar' => 'belum',
                    'catatan' => 'Pesanan mandiri via QR (berat/jumlah final ditimbang di outlet)',
                ]);
                foreach ($rows as $r) {
                    $order->items()->create($r);
                }
                $order->logs()->create(['status' => 'diterima']);

                // Poin loyalitas diberikan nanti saat order dibayar/lunas di outlet,
                // bukan saat pesanan mandiri dibuat (status awal: belum bayar).

                return $order;
            });
            if ($order) {
                $order->load('items.service');
            }
        }

        return view('register.success', ['customer' => $customer, 'order' => $order]);
    }
}
