<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Settings;
use App\Support\WhatsappNotifier;
use Illuminate\Http\Request;

/**
 * Pelacakan status order untuk PELANGGAN (publik, tanpa login).
 * Akses lewat token acak per order (link/QR) atau cari pakai nomor nota + HP.
 */
class TrackingController extends Controller
{
    /** Halaman cari order (form nota + no HP). */
    public function index()
    {
        return view('tracking.index');
    }

    /** Proses pencarian: cocokkan nomor nota dengan nomor HP pelanggan. */
    public function find(Request $request)
    {
        $validated = $request->validate([
            'nomor_nota' => 'required|string|max:40',
            'no_hp' => 'required|string|max:30',
        ], [
            'nomor_nota.required' => 'Nomor nota wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
        ]);

        $order = Order::withoutGlobalScopes()
            ->with('customer')
            ->where('nomor_nota', trim($validated['nomor_nota']))
            ->first();

        $inputPhone = WhatsappNotifier::normalizePhone($validated['no_hp']);
        $orderPhone = $order && $order->customer ? WhatsappNotifier::normalizePhone($order->customer->no_hp ?? '') : '';

        if (! $order || $orderPhone === '' || $orderPhone !== $inputPhone) {
            return back()
                ->withInput()
                ->with('error', 'Order tidak ditemukan. Pastikan nomor nota & nomor HP sesuai.');
        }

        return redirect()->route('track.show', $order->public_token);
    }

    /** Halaman status order (read-only) berdasarkan token publik. */
    public function show(string $token)
    {
        $order = Order::withoutGlobalScopes()
            ->with(['customer', 'items.service', 'logs', 'payments'])
            ->where('public_token', $token)
            ->firstOrFail();

        $branding = Settings::get($order->user_id)['branding'];

        return view('tracking.show', compact('order', 'branding'));
    }
}
