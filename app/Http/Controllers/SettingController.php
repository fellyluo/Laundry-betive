<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Settings::get();
        return view('settings.index', [
            'settings' => $settings,
            'colorPresets' => Settings::COLOR_PRESETS,
            'bgPresets' => Settings::BG_PRESETS,
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'nama_laundry' => 'required|string|max:255',
            'logo_emoji' => 'nullable|string|max:8',
            'logo_url' => 'nullable|string',
            'alamat_laundry' => 'nullable|string|max:255',
            'no_telp_laundry' => 'nullable|string|max:50',
            'theme_color' => 'required|string',
            'theme_color_font' => 'nullable|string',
            'theme_color_bg' => 'nullable|string',
            'theme_bg' => 'required|string',
            'theme_mode' => 'required|in:light,dark',
            'payment_methods' => 'nullable|array',
            'payment_methods.*.id' => 'required|string',
            'payment_methods.*.nama' => 'required|string',
            'payment_methods.*.aktif' => 'nullable',
            'payment_methods.*.no_rek' => 'nullable|string|max:120',
            'payment_methods.*.qris' => 'nullable|string',
        ], [
            'nama_laundry.required' => 'Nama laundry tidak boleh kosong.',
        ]);

        $methods = collect($validated['payment_methods'] ?? [])->map(fn ($m) => [
            'id' => $m['id'],
            'nama' => $m['nama'],
            'aktif' => filter_var($m['aktif'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'no_rek' => isset($m['no_rek']) && trim($m['no_rek']) !== '' ? trim($m['no_rek']) : null,
            'qris' => ! empty($m['qris']) ? $m['qris'] : null,
        ])->values()->all();

        if (empty($methods)) {
            $methods = Settings::defaults()['payment_methods'];
        }

        Settings::save([
            'branding' => [
                'nama_laundry' => trim($validated['nama_laundry']),
                'logo_emoji' => trim($validated['logo_emoji'] ?? '') ?: '🧺',
                'logo_url' => ! empty($validated['logo_url']) ? $validated['logo_url'] : null,
                'alamat_laundry' => trim($validated['alamat_laundry'] ?? '') ?: null,
                'no_telp_laundry' => trim($validated['no_telp_laundry'] ?? '') ?: null,
            ],
            'theme_color' => $validated['theme_color'],
            'theme_color_font' => $validated['theme_color_font'] ?? '#0d9488',
            'theme_color_bg' => $validated['theme_color_bg'] ?? '#0d9488',
            'theme_bg' => $validated['theme_bg'],
            'theme_mode' => $validated['theme_mode'],
            'payment_methods' => $methods,
        ]);

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function themeMode(Request $request)
    {
        $request->validate(['mode' => 'required|in:light,dark']);
        $settings = Settings::get();
        $settings['theme_mode'] = $request->input('mode');
        Settings::save($settings);
        return response()->json(['ok' => true, 'mode' => $settings['theme_mode']]);
    }
}
