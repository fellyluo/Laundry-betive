<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('created_at')->get();
        return view('services.index', compact('services'));
    }

    public function store(Request $request)
    {
        Service::create($this->validateService($request));
        return redirect()->route('services.index')->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validateService($request));
        return redirect()->route('services.index')->with('success', 'Layanan berhasil diperbarui.');
    }

    public function toggle(Service $service)
    {
        $service->update(['aktif' => ! $service->aktif]);
        return redirect()->route('services.index');
    }

    public function destroy(Service $service)
    {
        try {
            $service->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus layanan. Layanan mungkin sudah dipakai dalam order.');
        }
        return redirect()->route('services.index')->with('success', 'Layanan dihapus.');
    }

    private function validateService(Request $request): array
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'satuan' => 'required|in:kg,pcs',
            'tarif' => 'required|integer|min:1',
            'kategori' => 'required|in:laundry,sabun',
        ], [
            'nama.required' => 'Nama layanan wajib diisi',
            'tarif.required' => 'Tarif harus berupa angka positif',
            'tarif.min' => 'Tarif harus berupa angka positif',
        ]);

        return [
            'nama' => trim($validated['nama']),
            'satuan' => $validated['satuan'],
            'tarif' => (int) $validated['tarif'],
            'kategori' => $validated['kategori'],
            'aktif' => $request->boolean('aktif'),
        ];
    }
}
