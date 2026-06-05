<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\Settings;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    /** Public self-registration form (dibuka pelanggan via scan QR). */
    public function show()
    {
        $settings = Settings::get();
        $methods = collect($settings['payment_methods'])->where('aktif', true)->values();

        return view('register.form', ['methods' => $methods]);
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
        ], [
            'nama.required' => 'Nama wajib diisi',
            'no_hp.required' => 'Nomor HP wajib diisi',
        ]);

        // Cegah duplikat berdasarkan nomor HP
        $existing = Customer::where('no_hp', trim($validated['no_hp']))->first();
        if ($existing) {
            $existing->update([
                'nama' => trim($validated['nama']),
                'alamat' => isset($validated['alamat']) && trim($validated['alamat']) !== '' ? trim($validated['alamat']) : $existing->alamat,
                'metode_bayar' => $validated['metode_bayar'] ?? $existing->metode_bayar,
            ]);
            $customer = $existing;
        } else {
            $customer = Customer::create([
                'nama' => trim($validated['nama']),
                'no_hp' => trim($validated['no_hp']),
                'alamat' => isset($validated['alamat']) && trim($validated['alamat']) !== '' ? trim($validated['alamat']) : null,
                'metode_bayar' => $validated['metode_bayar'] ?? null,
                'poin' => 0,
                'via_qr' => true,
            ]);
        }

        return view('register.success', ['customer' => $customer]);
    }
}
