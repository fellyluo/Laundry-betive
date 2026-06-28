<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /** Riwayat poin (ledger) seorang pelanggan. */
    public function points(Customer $customer)
    {
        $transactions = $customer->pointTransactions()
            ->with('order:id,nomor_nota')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('customers.poin', compact('customer', 'transactions'));
    }

    /** Halaman dompet saldo: saldo, form top-up, & riwayat mutasi. */
    public function wallet(Customer $customer)
    {
        $transactions = $customer->walletTransactions()
            ->with('order:id,nomor_nota')
            ->orderByDesc('created_at')
            ->paginate(20);

        $methods = collect(Settings::get()['payment_methods'])->where('aktif', true)->values();

        return view('customers.saldo', compact('customer', 'transactions', 'methods'));
    }

    /** Tambah saldo (top-up) ke dompet pelanggan. */
    public function topup(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'metode' => 'nullable|string|max:50',
        ], [
            'jumlah.required' => 'Nominal top-up wajib diisi.',
            'jumlah.min' => 'Nominal top-up minimal Rp 1.',
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $customer->increment('saldo', $validated['jumlah']);
            $customer->walletTransactions()->create([
                'user_id' => $customer->user_id,
                'type' => 'topup',
                'amount' => (int) $validated['jumlah'],
                'metode' => $validated['metode'] ?? 'cash',
                'note' => 'Top-up saldo',
            ]);
        });

        return redirect()->route('customers.wallet', $customer)
            ->with('success', 'Top-up saldo berhasil: '.format_rupiah($validated['jumlah']).'.');
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
