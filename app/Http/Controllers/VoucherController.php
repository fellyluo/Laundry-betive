<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::orderByDesc('created_at')->get();

        return view('vouchers.index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        Voucher::create($this->validateVoucher($request) + ['terpakai' => 0]);

        return redirect()->route('vouchers.index')->with('success', 'Voucher berhasil dibuat.');
    }

    public function update(Request $request, Voucher $voucher)
    {
        $voucher->update($this->validateVoucher($request, $voucher));

        return redirect()->route('vouchers.index')->with('success', 'Voucher berhasil diperbarui.');
    }

    public function toggle(Voucher $voucher)
    {
        $voucher->update(['aktif' => ! $voucher->aktif]);

        return redirect()->route('vouchers.index');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();

        return redirect()->route('vouchers.index')->with('success', 'Voucher dihapus.');
    }

    private function validateVoucher(Request $request, ?Voucher $ignore = null): array
    {
        // Normalisasi kode -> huruf besar tanpa spasi.
        $request->merge(['kode' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('kode')))]);

        $validated = $request->validate([
            'kode' => [
                'required', 'string', 'max:40', 'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('vouchers', 'kode')
                    ->where('user_id', auth()->id())
                    ->ignore($ignore?->id),
            ],
            'tipe' => 'required|in:nominal,persen',
            'nilai' => ['required', 'integer', 'min:1', $request->input('tipe') === 'persen' ? 'max:100' : 'max:100000000'],
            'min_belanja' => 'nullable|integer|min:0',
            'kuota' => 'nullable|integer|min:1',
            'kadaluarsa' => 'nullable|date',
        ], [
            'kode.required' => 'Kode voucher wajib diisi.',
            'kode.regex' => 'Kode hanya boleh huruf, angka, dan tanda hubung.',
            'kode.unique' => 'Kode voucher ini sudah ada.',
            'nilai.required' => 'Nilai voucher wajib diisi.',
            'nilai.max' => 'Persentase maksimal 100%.',
        ]);

        return [
            'kode' => $validated['kode'],
            'tipe' => $validated['tipe'],
            'nilai' => (int) $validated['nilai'],
            'min_belanja' => (int) ($validated['min_belanja'] ?? 0),
            'kuota' => isset($validated['kuota']) && $validated['kuota'] !== null ? (int) $validated['kuota'] : null,
            'kadaluarsa' => $validated['kadaluarsa'] ?? null,
            'aktif' => $request->boolean('aktif'),
        ];
    }
}
