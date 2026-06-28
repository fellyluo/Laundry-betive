<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Support\Settings;
use App\Support\WhatsappNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'semua');
        $bayar = (string) $request->query('bayar', 'semua');

        $orders = Order::with(['customer', 'items.service', 'payments'])
            ->when($q !== '', fn ($w) => $w->where(function ($x) use ($q) {
                $x->where('nomor_nota', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('nama', 'like', "%{$q}%")->orWhere('no_hp', 'like', "%{$q}%"));
            }))
            ->when($status !== 'semua' && $status !== '', fn ($w) => $w->where('status', $status))
            // "Belum bayar" mencakup DP (sebagian) agar konsisten dengan dashboard.
            ->when($bayar === 'belum', fn ($w) => $w->whereIn('status_bayar', ['belum', 'dp']))
            ->when($bayar === 'lunas', fn ($w) => $w->where('status_bayar', 'lunas'))
            ->orderByDesc('tanggal_masuk')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', compact('orders', 'q', 'status', 'bayar'));
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
            // Scope ke tenant: cegah member memakai customer milik laundry lain (exists biasa bypass global scope).
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', auth()->id())],
            'estimasi_selesai' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.service_id' => ['required', Rule::exists('services', 'id')->where('user_id', auth()->id())],
            'items.*.qty' => 'required|numeric|min:0.01',
            'status_bayar' => 'required|in:belum,lunas',
            'jumlah_bayar' => 'nullable|numeric|min:0',
            'metode_bayar' => 'nullable|string',
        ], [
            'customer_id.required' => 'Silakan pilih pelanggan terlebih dahulu.',
            'items.required' => 'Silakan tambahkan minimal 1 layanan dengan kuantitas yang valid.',
            'estimasi_selesai.required' => 'Silakan tentukan estimasi selesai laundry.',
        ]);

        $order = DB::transaction(function () use ($validated) {
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

            // Pembayaran awal (jika ada)
            $paidAmount = 0;
            if ($validated['status_bayar'] === 'lunas') {
                $paidAmount = $total;
            } elseif (! empty($validated['jumlah_bayar'])) {
                $paidAmount = (int) $validated['jumlah_bayar'];
            }

            // nomor_nota: YYYYMMDD-XXX (unik global lintas member)
            $prefix = Carbon::today()->format('Ymd');
            $seq = Order::withoutGlobalScopes()->whereDate('tanggal_masuk', Carbon::today())->count() + 1;
            do {
                $nota = $prefix.'-'.str_pad($seq, 3, '0', STR_PAD_LEFT);
                $seq++;
            } while (Order::withoutGlobalScopes()->where('nomor_nota', $nota)->exists());

            $order = Order::create([
                'nomor_nota' => $nota,
                'public_token' => Order::generatePublicToken(),
                'customer_id' => $validated['customer_id'],
                'tanggal_masuk' => now(),
                'estimasi_selesai' => Carbon::parse($validated['estimasi_selesai']),
                'status' => 'diterima',
                'total' => $total,
                'status_bayar' => 'belum',
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($itemRows as $r) {
                $order->items()->create($r);
            }

            if ($paidAmount > 0) {
                $order->payments()->create([
                    'jumlah' => $paidAmount,
                    'metode' => ($validated['metode_bayar'] ?? null) ?: 'cash',
                ]);
            }

            $order->logs()->create(['status' => 'diterima']);

            // Selaraskan status bayar + beri poin loyalitas bila langsung lunas.
            $order->syncPaymentStatus();

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
            // Scope ke tenant: cegah member memakai customer milik laundry lain (exists biasa bypass global scope).
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', auth()->id())],
            'estimasi_selesai' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.service_id' => ['required', Rule::exists('services', 'id')->where('user_id', auth()->id())],
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

            $order->update([
                'customer_id' => $validated['customer_id'],
                'estimasi_selesai' => Carbon::parse($validated['estimasi_selesai']),
                'catatan' => $validated['catatan'] ?? null,
                'total' => $total,
            ]);

            // Total berubah -> selaraskan status bayar (dan beri poin bila kini lunas).
            $order->syncPaymentStatus();
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
            'loyalty' => Settings::loyalty($order->user_id),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:diterima,diproses,selesai,diambil,dibatalkan',
        ]);

        $target = $validated['status'];

        // No-op: status sama, abaikan tanpa error.
        if ($order->status === $target) {
            return redirect()->route('orders.show', $order);
        }

        // Hanya izinkan transisi status yang masuk akal (cegah loncat/ubah status final).
        $allowed = [
            'diterima' => ['diproses', 'dibatalkan'],
            'diproses' => ['selesai', 'dibatalkan'],
            'selesai' => ['diambil', 'dibatalkan'],
            'diambil' => [],
            'dibatalkan' => [],
        ];

        if (! in_array($target, $allowed[$order->status] ?? [], true)) {
            return redirect()->route('orders.show', $order)
                ->with('error', "Perubahan status dari \"{$order->status}\" ke \"{$target}\" tidak diperbolehkan.");
        }

        $order->update(['status' => $target]);
        $order->logs()->create(['status' => $target]);

        // Pembatalan: tarik kembali poin yang sempat diberikan & kembalikan poin yang sempat ditukar.
        if ($target === 'dibatalkan') {
            $order->reverseLoyaltyOnCancel();
        }

        // Cucian selesai: kirim notifikasi WhatsApp otomatis (jika diaktifkan member).
        if ($target === 'selesai' && Settings::whatsapp($order->user_id)['enabled']) {
            if (! WhatsappNotifier::sendOrderDone($order)) {
                return redirect()->route('orders.show', $order)
                    ->with('error', 'Status diperbarui, tapi notifikasi WhatsApp gagal terkirim. Periksa token Fonnte & nomor HP pelanggan.');
            }

            return redirect()->route('orders.show', $order)->with('success', 'Status diperbarui & notifikasi WhatsApp terkirim ke pelanggan.');
        }

        return redirect()->route('orders.show', $order);
    }

    /** Tukar poin pelanggan jadi potongan (diskon) pada order yang belum lunas. */
    public function redeemPoints(Request $request, Order $order)
    {
        if (in_array($order->status, ['diambil', 'dibatalkan'], true)) {
            return back()->with('error', 'Order sudah diambil/dibatalkan — poin tidak bisa ditukar.');
        }
        if ($order->status_bayar === 'lunas') {
            return back()->with('error', 'Order sudah lunas — tidak perlu menukar poin.');
        }

        $validated = $request->validate([
            'poin' => 'required|integer|min:1',
        ]);
        $poin = (int) $validated['poin'];

        $customer = $order->customer()->first();
        if (! $customer) {
            return back()->with('error', 'Order ini tidak memiliki data pelanggan.');
        }

        $loyalty = Settings::loyalty($order->user_id);
        $poinValue = $loyalty['poin_value'];
        $minRedeem = $loyalty['min_redeem'];

        if ($poinValue <= 0) {
            return back()->with('error', 'Penukaran poin sedang dinonaktifkan.');
        }
        if ($poin < $minRedeem) {
            return back()->with('error', "Minimal penukaran {$minRedeem} poin.");
        }
        if ($poin > (int) $customer->poin) {
            return back()->with('error', 'Poin pelanggan tidak mencukupi.');
        }

        $paid = (int) $order->payments()->sum('jumlah');
        $sisaTagihan = $order->netTotal() - $paid; // jangan sampai potongan bikin lebih bayar
        $disc = $poin * $poinValue;
        if ($disc > $sisaTagihan) {
            return back()->with('error', 'Potongan poin melebihi sisa tagihan order.');
        }

        DB::transaction(function () use ($order, $customer, $poin, $disc) {
            $customer->decrement('poin', $poin);
            $order->diskon_poin = (int) $order->diskon_poin + $disc;
            $order->poin_redeemed = (int) $order->poin_redeemed + $poin;
            $order->save();

            $order->pointTransactions()->create([
                'user_id' => $order->user_id,
                'customer_id' => $order->customer_id,
                'type' => 'redeem',
                'points' => -$poin,
                'note' => 'Tukar poin jadi potongan order '.$order->nomor_nota,
            ]);

            // Potongan bisa membuat order langsung lunas -> selaraskan status & poin earn.
            $order->syncPaymentStatus();
        });

        return redirect()->route('orders.show', $order)
            ->with('success', "Berhasil menukar {$poin} poin menjadi potongan ".format_rupiah($disc).'.');
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

        // Selaraskan status bayar + beri poin loyalitas bila pembayaran ini melunasi order.
        $order->syncPaymentStatus();

        return redirect()->route('orders.show', $order);
    }
}
