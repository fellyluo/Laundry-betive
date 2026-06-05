<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderByDesc('created_at')->get();
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCustomer($request);
        $customer = Customer::create($data + ['poin' => 0]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($customer);
        }
        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validateCustomer($request);
        $customer->update($data);
        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus pelanggan. Pelanggan mungkin terikat dengan order.');
        }
        return redirect()->route('customers.index')->with('success', 'Pelanggan dihapus.');
    }

    private function validateCustomer(Request $request): array
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => ['required', 'string', 'max:30', function ($attr, $value, $fail) {
                if (strlen(preg_replace('/[^0-9]/', '', $value)) < 8) {
                    $fail('Nomor HP tidak valid (minimal 8 angka)');
                }
            }],
            'alamat' => 'nullable|string',
        ], [
            'nama.required' => 'Nama pelanggan wajib diisi',
            'no_hp.required' => 'Nomor HP wajib diisi',
        ]);

        return [
            'nama' => trim($validated['nama']),
            'no_hp' => trim($validated['no_hp']),
            'alamat' => isset($validated['alamat']) && trim($validated['alamat']) !== '' ? trim($validated['alamat']) : null,
        ];
    }
}
