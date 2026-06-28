<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $customers = Customer::when($q !== '', function ($w) use ($q) {
            $w->where('nama', 'like', "%{$q}%")
                ->orWhere('no_hp', 'like', "%{$q}%")
                ->orWhere('alamat', 'like', "%{$q}%");
        })
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('customers.index', compact('customers', 'q'));
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
        // Cegah data order jadi yatim / kehilangan identitas pelanggan.
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Pelanggan tidak bisa dihapus karena masih punya riwayat order.');
        }

        $customer->delete();

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
