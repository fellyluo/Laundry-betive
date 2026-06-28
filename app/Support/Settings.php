<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class Settings
{
    /** Template default notifikasi WhatsApp saat cucian selesai. */
    public const DEFAULT_WA_TEMPLATE = 'Halo {nama}, cucian Anda dengan nota *{nota}* di *{laundry}* sudah *SELESAI* dan siap diambil. 🧺 Sisa tagihan: {sisa}. Terima kasih!';

    /** Accent color presets (key => [accent, hover]) */
    public const COLOR_PRESETS = [
        'teal' => ['accent' => '#0d9488', 'hover' => '#0f766e'],
        'blue' => ['accent' => '#2563eb', 'hover' => '#1d4ed8'],
        'indigo' => ['accent' => '#4f46e5', 'hover' => '#4338ca'],
        'purple' => ['accent' => '#7c3aed', 'hover' => '#6d28d9'],
        'emerald' => ['accent' => '#059669', 'hover' => '#047857'],
        'rose' => ['accent' => '#e11d48', 'hover' => '#be123c'],
        'amber' => ['accent' => '#d97706', 'hover' => '#b45309'],
        'orange' => ['accent' => '#ea580c', 'hover' => '#c2410c'],
    ];

    /** Background presets (key => [color, label]) */
    public const BG_PRESETS = [
        'slate' => ['color' => '#0f172a', 'label' => 'Slate Blue (Default)'],
        'black' => ['color' => '#000000', 'label' => 'Pure Black (OLED)'],
        'navy' => ['color' => '#172554', 'label' => 'Vibrant Navy'],
        'charcoal' => ['color' => '#27272a', 'label' => 'Charcoal Grey'],
        'plum' => ['color' => '#3b0764', 'label' => 'Midnight Plum'],
        'forest' => ['color' => '#064e3b', 'label' => 'Forest Green'],
        'bronze' => ['color' => '#451a03', 'label' => 'Deep Bronze'],
        'indigo' => ['color' => '#1e1b4b', 'label' => 'Royal Indigo'],
        'maroon' => ['color' => '#4c0519', 'label' => 'Dark Maroon'],
        'frost' => ['color' => '#334155', 'label' => 'Nordic Frost'],
    ];

    public static function defaults(): array
    {
        return [
            'branding' => [
                'nama_laundry' => 'LaundryPro Premium',
                'logo_emoji' => '🧺',
                'logo_url' => null,
                'alamat_laundry' => 'Jl. Raya Utama No. 42, Jakarta',
                'no_telp_laundry' => '08123456789',
            ],
            'theme_color' => 'teal',
            'theme_color_font' => '#0d9488',
            'theme_color_bg' => '#0d9488',
            'theme_bg' => 'slate',
            'theme_mode' => 'dark',
            'payment_methods' => [
                ['id' => 'cash', 'nama' => 'Tunai (Cash)', 'aktif' => true],
                ['id' => 'qris', 'nama' => 'QRIS (Gopay/Ovo/Dana)', 'aktif' => true],
                ['id' => 'transfer', 'nama' => 'Transfer Bank BCA', 'aktif' => true],
            ],
            'loyalty' => [
                'earn_rate' => 10000,  // Rp belanja untuk dapat 1 poin (saat order lunas)
                'poin_value' => 1000,  // nilai potongan (Rp) per 1 poin saat ditukar
                'min_redeem' => 10,    // minimal poin untuk sekali penukaran
            ],
            'whatsapp' => [
                'enabled' => false,
                'token' => '',
                'template_selesai' => self::DEFAULT_WA_TEMPLATE,
            ],
        ];
    }

    /** Konfigurasi notifikasi WhatsApp (ternormalisasi). */
    public static function whatsapp(?int $userId = null): array
    {
        $s = func_num_args() === 0 ? self::get() : self::get($userId);
        $w = is_array($s['whatsapp'] ?? null) ? $s['whatsapp'] : [];
        $tpl = trim((string) ($w['template_selesai'] ?? ''));

        return [
            'enabled' => (bool) ($w['enabled'] ?? false),
            'token' => trim((string) ($w['token'] ?? '')),
            'template_selesai' => $tpl !== '' ? $tpl : self::DEFAULT_WA_TEMPLATE,
        ];
    }

    /** Konfigurasi loyalitas (ternormalisasi & aman dipakai untuk perhitungan). */
    public static function loyalty(?int $userId = null): array
    {
        $s = func_num_args() === 0 ? self::get() : self::get($userId);
        $l = is_array($s['loyalty'] ?? null) ? $s['loyalty'] : [];

        return [
            'earn_rate' => max(0, (int) ($l['earn_rate'] ?? 10000)),
            'poin_value' => max(0, (int) ($l['poin_value'] ?? 1000)),
            'min_redeem' => max(1, (int) ($l['min_redeem'] ?? 10)),
        ];
    }

    /** Pemilik settings yang sedang berlaku: member -> id-nya; super admin/guest -> null (platform). */
    public static function tenantId(): ?int
    {
        $u = Auth::user();

        return ($u && $u->role === 'member') ? $u->id : null;
    }

    /** Fetch settings (per member, atau platform jika $userId null) merged onto defaults. */
    public static function get(?int $userId = null): array
    {
        if (func_num_args() === 0) {
            $userId = self::tenantId();
        }
        $row = Setting::withoutGlobalScopes()->where('user_id', $userId)->first();
        $value = ($row && is_array($row->value)) ? $row->value : [];

        return array_replace_recursive(self::defaults(), $value);
    }

    public static function save(array $value, ?int $userId = null): void
    {
        if (func_num_args() < 2) {
            $userId = self::tenantId();
        }
        $row = Setting::withoutGlobalScopes()->where('user_id', $userId)->first();
        if ($row) {
            $row->update(['value' => $value]);
        } else {
            Setting::create(['user_id' => $userId, 'value' => $value]);
        }
    }

    /** Darken a hex color by a percentage. */
    public static function darken(string $hex, int $percent): string
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }
        if (strlen($hex) < 6) {
            return '#0f766e';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = max(0, min(255, (int) floor($r * (1 - $percent / 100))));
        $g = max(0, min(255, (int) floor($g * (1 - $percent / 100))));
        $b = max(0, min(255, (int) floor($b * (1 - $percent / 100))));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /** Resolve the accent + background css values for a settings array. */
    public static function theme(array $s): array
    {
        $mode = $s['theme_mode'] ?? 'dark';

        if (($s['theme_color'] ?? 'teal') === 'custom') {
            $accentFont = $s['theme_color_font'] ?? '#0d9488';
            $accentBg = $s['theme_color_bg'] ?? '#0d9488';
            $accentHover = self::darken($accentBg, 12);
        } else {
            $preset = self::COLOR_PRESETS[$s['theme_color'] ?? 'teal'] ?? self::COLOR_PRESETS['teal'];
            $accentFont = $preset['accent'];
            $accentBg = $preset['accent'];
            $accentHover = $preset['hover'];
        }

        $bg = self::BG_PRESETS[$s['theme_bg'] ?? 'slate'] ?? self::BG_PRESETS['slate'];

        return [
            'mode' => $mode,
            'accent_font' => $accentFont,
            'accent_bg' => $accentBg,
            'accent_hover' => $accentHover,
            'background' => $mode === 'light' ? '#f8fafc' : $bg['color'],
        ];
    }
}
