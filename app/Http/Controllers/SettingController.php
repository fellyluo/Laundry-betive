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
            'loyalty' => Settings::loyalty(),
            'discount' => Settings::discount(),
            'whatsapp' => Settings::whatsapp(),
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
            'loyalty_earn_rate' => 'nullable|integer|min:100',
            'loyalty_poin_value' => 'nullable|integer|min:0',
            'loyalty_min_redeem' => 'nullable|integer|min:1',
            'wa_token' => 'nullable|string|max:255',
            'wa_template_selesai' => 'nullable|string|max:1000',
        ], [
            'nama_laundry.required' => 'Nama laundry tidak boleh kosong.',
            'loyalty_earn_rate.min' => 'Rp per poin minimal 100.',
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
            'loyalty' => [
                'enabled' => $request->boolean('loyalty_enabled'),
                'earn_rate' => max(100, (int) ($validated['loyalty_earn_rate'] ?? 10000)),
                'poin_value' => max(0, (int) ($validated['loyalty_poin_value'] ?? 1000)),
                'min_redeem' => max(1, (int) ($validated['loyalty_min_redeem'] ?? 10)),
            ],
            'discount' => [
                'enabled' => $request->boolean('discount_enabled'),
            ],
            'whatsapp' => [
                'enabled' => filter_var($request->input('wa_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'token' => trim((string) ($validated['wa_token'] ?? '')),
                'template_selesai' => trim((string) ($validated['wa_template_selesai'] ?? '')) ?: Settings::DEFAULT_WA_TEMPLATE,
            ],
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
