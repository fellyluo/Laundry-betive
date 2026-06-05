<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Payment;
use App\Models\StatusLog;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'items.service', 'payments'])
            ->orderByDesc('tanggal_masuk')
            ->get();
        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $customers = Customer::orderByDesc('created_at')->get();
        $services = Service::where('aktif', true)->orderBy('nama')->get();
        $settings = Settings::get();
        $methods = collect($settings['payment_methods'])->where('aktif', true)->values();

        return view('orders.create', compact('customers', 'services', 'methods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'estimasi_selesai' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'status_bayar' => 'required|in:belum,lunas',
            'jumlah_bayar' => 'nullable|numeric|min:0',
            'metode_bayar' => 'nullable|string',
        ], [
            'customer_id.required' => 'Silakan pilih pelanggan terlebih dahulu.',
            'items.required' => 'Silakan tambahkan minimal 1 layanan dengan kuantitas yang valid.',
            'estimasi_selesai.required' => 'Silakan tentukan estimasi selesai laundry.',
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            // Snapshot prices + compute total
            $services = Service::whereIn('id', collect($validated['items'])->pluck('service_id'))->get()->keyBy('id');
            $total = 0;
            $itemRows = [];
            foreach ($validated['items'] as $row) {
                $svc = $services[$row['service_id']] ?? null;
                if (! $svc) {
                    continue;
                }
                $qty = (float) $row['qty'];
                $harga = (int) $svc->tarif;
                $subtotal = (int) round($qty * $harga);
                $total += $subtotal;
                $itemRows[] = [
                    'service_id' => $svc->id,
                    'qty' => $qty,
                    'harga_satuan' => $harga,
                    'subtotal' => $subtotal,
                ];
            }

            // Payment
            $statusBayar = 'belum';
            $paidAmount = 0;
            if ($validated['status_bayar'] === 'lunas') {
                $paidAmount = $total;
            } elseif (! empty($validated['jumlah_bayar'])) {
                $paidAmount = (int) $validated['jumlah_bayar'];
            }
            if ($paidAmount > 0 && $paidAmount >= $total) {
                $statusBayar = 'lunas';
            }

            // nomor_nota: YYYYMMDD-XXX
            $prefix = Carbon::today()->format('Ymd');
            $seq = Order::whereDate('tanggal_masuk', Carbon::today())->count() + 1;
            do {
                $nota = $prefix . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
                $seq++;
            } while (Order::where('nomor_nota', $nota)->exists());

            $order = Order::create([
                'nomor_nota' => $nota,
                'customer_id' => $validated['customer_id'],
                'tanggal_masuk' => now(),
                'estimasi_selesai' => Carbon::parse($validated['estimasi_selesai']),
                'status' => 'diterima',
                'total' => $total,
                'status_bayar' => $statusBayar,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($itemRows as $r) {
                $order->items()->create($r);
            }

            if ($paidAmount > 0) {
                $order->payments()->create([
                    'jumlah' => $paidAmount,
                    'metode' => $validated['metode_bayar'] ?: 'cash',
                ]);
            }

            $order->logs()->create(['status' => 'diterima']);

            // Loyalty points: 1 per Rp 10.000
            $added = intdiv($total, 10000);
            if ($added > 0) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->increment('poin', $added);
                }
            }

            return $order;
        });

        return redirect()->route('orders.show', $order);
    }

    public function edit(Order $order)
    {
        if (in_array($order->status, ['diambil', 'dibatalkan'])) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order yang sudah diambil/dibatalkan tidak bisa diedit.');
        }

        $order->load(['customer', 'items.service']);
        $customers = Customer::orderByDesc('created_at')->get();

        // Layanan aktif + layanan yang sudah dipakai di order (walau non-aktif) agar tetap tampil
        $active = Service::where('aktif', true)->orderBy('nama')->get();
        $usedIds = $order->items->pluck('service_id')->filter()->all();
        $used = Service::whereIn('id', $usedIds)->get();
        $services = $active->concat($used)->unique('id')->sortBy('nama')->values();

        return view('orders.edit', compact('order', 'customers', 'services'));
    }

    public function update(Request $request, Order $order)
    {
        if (in_array($order->status, ['diambil', 'dibatalkan'])) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order yang sudah diambil/dibatalkan tidak bisa diedit.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'estimasi_selesai' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.qty' => 'required|numeric|min:0.01',
        ], [
            'customer_id.required' => 'Silakan pilih pelanggan terlebih dahulu.',
            'items.required' => 'Silakan tambahkan minimal 1 layanan dengan kuantitas yang valid.',
            'estimasi_selesai.required' => 'Silakan tentukan estimasi selesai laundry.',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $services = Service::whereIn('id', collect($validated['items'])->pluck('service_id'))->get()->keyBy('id');
            $total = 0;
            $rows = [];
            foreach ($validated['items'] as $row) {
                $svc = $services[$row['service_id']] ?? null;
                if (! $svc) {
                    continue;
                }
                $qty = (float) $row['qty'];
                $harga = (int) $svc->tarif;
                $subtotal = (int) round($qty * $harga);
                $total += $subtotal;
                $rows[] = ['service_id' => $svc->id, 'qty' => $qty, 'harga_satuan' => $harga, 'subtotal' => $subtotal];
            }

            $order->items()->delete();
            foreach ($rows as $r) {
                $order->items()->create($r);
            }

            $paid = (int) $order->payments()->sum('jumlah');
            $order->update([
                'customer_id' => $validated['customer_id'],
                'estimasi_selesai' => Carbon::parse($validated['estimasi_selesai']),
                'catatan' => $validated['catatan'] ?? null,
                'total' => $total,
                'status_bayar' => $paid >= $total && $total > 0 ? 'lunas' : 'belum',
            ]);
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order berhasil diperbarui.');
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'items.service', 'payments', 'logs']);
        $settings = Settings::get();
        $methods = collect($settings['payment_methods'])->where('aktif', true)->values();

        return view('orders.show', [
            'order' => $order,
            'branding' => $settings['branding'],
            'methods' => $methods,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:diterima,diproses,selesai,diambil,dibatalkan',
        ]);

        if ($order->status !== $validated['status']) {
            $order->update(['status' => $validated['status']]);
            $order->logs()->create(['status' => $validated['status']]);
        }

        return redirect()->route('orders.show', $order);
    }

    public function addPayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'jumlah_bayar' => 'required|numeric|min:1',
            'metode_bayar' => 'required|string',
        ]);

        $order->payments()->create([
            'jumlah' => (int) $validated['jumlah_bayar'],
            'metode' => $validated['metode_bayar'],
        ]);

        $totalPaid = $order->payments()->sum('jumlah');
        $order->update(['status_bayar' => $totalPaid >= $order->total ? 'lunas' : 'belum']);

        return redirect()->route('orders.show', $order);
    }
}
