<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Total dihitung langsung di DB agar tetap akurat lintas halaman (bukan hanya halaman aktif).
        $totalToday = (int) Expense::whereDate('tanggal', $today)->sum('jumlah');
        $totalMonth = (int) Expense::whereYear('tanggal', $today->year)
            ->whereMonth('tanggal', $today->month)->sum('jumlah');
        $totalAll = (int) Expense::sum('jumlah');

        $expenses = Expense::orderByDesc('tanggal')->orderByDesc('id')->paginate(20);

        return view('expenses.index', compact('expenses', 'totalToday', 'totalMonth', 'totalAll'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'keterangan' => 'required|string|max:255',
            'kategori' => 'required|string|max:50',
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'nullable|date',
        ], [
            'keterangan.required' => 'Keterangan pengeluaran wajib diisi',
            'jumlah.required' => 'Jumlah harus berupa angka positif',
            'jumlah.min' => 'Jumlah harus berupa angka positif',
        ]);

        Expense::create([
            'keterangan' => trim($validated['keterangan']),
            'kategori' => $validated['kategori'],
            'jumlah' => (int) $validated['jumlah'],
            'tanggal' => ! empty($validated['tanggal']) ? Carbon::parse($validated['tanggal']) : now(),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran dihapus.');
    }
}
