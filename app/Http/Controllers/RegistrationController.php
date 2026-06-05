<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Service;
use App\Models\Order;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegistrationController extends Controller
{
    /** Public self-registration + order form (dibuka pelanggan via scan QR). */
    public function show()
    {
        $settings = Settings::get();
        $methods = collect($settings['payment_methods'])->where('aktif', true)->values();
        $services = Service::where('aktif', true)->orderBy('kategori')->orderBy('nama')->get();

        return view('register.form', ['methods' => $methods, 'services' => $services]);
    }

    public function store(Request $request)
    {
        $settings = Settings::get();
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
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.qty' => 'required|numeric|min:0.1',
        ], [
            'nama.required' => 'Nama wajib diisi',
            'no_hp.required' => 'Nomor HP wajib diisi',
        ]);

        // Customer (cegah duplikat by HP)
        $customer = Customer::where('no_hp', trim($validated['no_hp']))->first();
        $custData = [
            'nama' => trim($validated['nama']),
            'alamat' => isset($validated['alamat']) && trim($validated['alamat']) !== '' ? trim($validated['alamat']) : ($customer->alamat ?? null),
            'metode_bayar' => $validated['metode_bayar'] ?? ($customer->metode_bayar ?? null),
        ];
        if ($customer) {
            $customer->update($custData);
        } else {
            $customer = Customer::create($custData + ['no_hp' => trim($validated['no_hp']), 'poin' => 0, 'via_qr' => true]);
        }

        // Buat order bila ada layanan dipilih
        $order = null;
        $items = collect($validated['items'] ?? [])->filter(fn ($i) => ! empty($i['service_id']) && (float) ($i['qty'] ?? 0) > 0);
        if ($items->isNotEmpty()) {
            $order = DB::transaction(function () use ($items, $customer) {
                $services = Service::whereIn('id', $items->pluck('service_id'))->get()->keyBy('id');
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

                $prefix = Carbon::today()->format('Ymd');
                $seq = Order::whereDate('tanggal_masuk', Carbon::today())->count() + 1;
                do {
                    $nota = $prefix . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
                    $seq++;
                } while (Order::where('nomor_nota', $nota)->exists());

                $order = Order::create([
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

                $added = intdiv($total, 10000);
                if ($added > 0) {
                    $customer->increment('poin', $added);
                }

                return $order;
            });
            $order->load('items.service');
        }

        return view('register.success', ['customer' => $customer, 'order' => $order]);
    }
}
