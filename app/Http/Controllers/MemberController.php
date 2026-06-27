<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Setting;
use App\Models\StatusLog;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    /** Halaman pengaturan platform super admin (logo, nama, tema) + akun. */
    public function settings()
    {
        $settings = Settings::get(null); // platform (user_id null)
        return view('superadmin.settings', [
            'settings' => $settings,
            'colorPresets' => Settings::COLOR_PRESETS,
            'bgPresets' => Settings::BG_PRESETS,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'nama_laundry' => 'required|string|max:255',
            'logo_emoji' => 'nullable|string|max:8',
            'logo_url' => 'nullable|string',
            'theme_color' => 'required|string',
            'theme_color_font' => 'nullable|string',
            'theme_color_bg' => 'nullable|string',
            'theme_bg' => 'required|string',
        ], ['nama_laundry.required' => 'Nama platform tidak boleh kosong.']);

        $current = Settings::get(null);

        Settings::save([
            'branding' => [
                'nama_laundry' => trim($validated['nama_laundry']),
                'logo_emoji' => trim($validated['logo_emoji'] ?? '') ?: '🧺',
                'logo_url' => ! empty($validated['logo_url']) ? $validated['logo_url'] : null,
                'alamat_laundry' => $current['branding']['alamat_laundry'] ?? null,
                'no_telp_laundry' => $current['branding']['no_telp_laundry'] ?? null,
            ],
            'theme_color' => $validated['theme_color'],
            'theme_color_font' => $validated['theme_color_font'] ?? '#0d9488',
            'theme_color_bg' => $validated['theme_color_bg'] ?? '#0d9488',
            'theme_bg' => $validated['theme_bg'],
            'theme_mode' => $current['theme_mode'] ?? 'dark',
            'payment_methods' => $current['payment_methods'] ?? Settings::defaults()['payment_methods'],
        ], null);

        return redirect()->route('platform.settings')->with('success', 'Pengaturan platform berhasil disimpan.');
    }

    public function index()
    {
        $members = User::orderByRaw("CASE WHEN role='super_admin' THEN 0 ELSE 1 END")
            ->orderBy('username')
            ->get();

        $stats = [
            'total' => $members->where('role', 'member')->count(),
            'aktif' => $members->where('role', 'member')->filter(fn ($u) => ! $u->isBlocked())->count(),
            'blokir' => $members->where('role', 'member')->filter(fn ($u) => $u->isBlocked())->count(),
        ];

        return view('superadmin.members', compact('members', 'stats'));
    }

    /** Super admin mengubah profil sendiri (nama, username, password opsional). */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:8',
        ], [
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah dipakai',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, underscore',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        $data = [
            'name' => trim($validated['name'] ?? '') ?: null,
            'username' => $validated['username'],
        ];
        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'password' => 'required|string|min:8',
            'plan' => 'nullable|string|max:50',
            'plan_price' => 'nullable|integer|min:0',
            'subscribed_until' => 'nullable|date',
        ], [
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah dipakai',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, underscore',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        User::create([
            'name' => trim($validated['name'] ?? '') ?: null,
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => 'member',
            'is_active' => true,
            'plan' => $validated['plan'] ?? null,
            'plan_price' => (int) ($validated['plan_price'] ?? 0),
            'subscribed_until' => $validated['subscribed_until'] ?? null,
        ]);

        return redirect()->route('members.index')->with('success', 'Member berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $this->guardSuperAdmin($user);

        $validated = $request->validate([
            'plan' => 'nullable|string|max:50',
            'plan_price' => 'nullable|integer|min:0',
            'subscribed_until' => 'nullable|date',
            'is_active' => 'nullable',
        ]);

        $user->update([
            'plan' => $validated['plan'] ?? null,
            'plan_price' => (int) ($validated['plan_price'] ?? 0),
            'subscribed_until' => $validated['subscribed_until'] ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('members.index')->with('success', 'Langganan member diperbarui.');
    }

    public function toggle(User $user)
    {
        $this->guardSuperAdmin($user);
        $user->update(['is_active' => ! $user->is_active]);
        return redirect()->route('members.index')->with('success', $user->is_active ? 'Member diaktifkan.' : 'Member di-suspend.');
    }

    public function password(Request $request, User $user)
    {
        $validated = $request->validate(['password' => 'required|string|min:8'], [
            'password.min' => 'Password minimal 8 karakter',
        ]);
        $user->update(['password' => Hash::make($validated['password'])]);
        return redirect()->route('members.index')->with('success', 'Password member diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->guardSuperAdmin($user);
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $this->purgeTenantData($user->id);
        $user->delete();

        return redirect()->route('members.index')->with('success', 'Member beserta seluruh datanya dihapus.');
    }

    /**
     * Hapus seluruh data milik seorang member (tidak ada FK user_id di DB,
     * jadi cascade dilakukan di level aplikasi) agar tidak ada data yatim.
     */
    private function purgeTenantData(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $orderIds = Order::withoutGlobalScopes()->where('user_id', $userId)->pluck('id');
            if ($orderIds->isNotEmpty()) {
                Payment::whereIn('order_id', $orderIds)->delete();
                StatusLog::whereIn('order_id', $orderIds)->delete();
                OrderItem::whereIn('order_id', $orderIds)->delete();
                Order::withoutGlobalScopes()->whereIn('id', $orderIds)->delete();
            }
            Customer::withoutGlobalScopes()->where('user_id', $userId)->delete();
            Service::withoutGlobalScopes()->where('user_id', $userId)->delete();
            Expense::withoutGlobalScopes()->where('user_id', $userId)->delete();
            Setting::withoutGlobalScopes()->where('user_id', $userId)->delete();
        });
    }

    /** Cegah super admin lain diubah/dihapus dari sini. */
    private function guardSuperAdmin(User $user): void
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat dikelola sebagai member.');
        }
    }
}
