<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::orderByDesc('tanggal')->orderByDesc('id')->get();

        $today = Carbon::today();
        $totalToday = $expenses->filter(fn ($e) => $e->tanggal && $e->tanggal->isSameDay($today))->sum('jumlah');
        $totalMonth = $expenses->filter(fn ($e) => $e->tanggal && $e->tanggal->format('Y-m') === $today->format('Y-m'))->sum('jumlah');
        $totalAll = $expenses->sum('jumlah');

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
