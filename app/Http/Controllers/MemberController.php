<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
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
            'password' => 'nullable|string|min:6',
        ], [
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah dipakai',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, underscore',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        $data = [
            'name' => trim($validated['name'] ?? '') ?: null,
            'username' => $validated['username'],
        ];
        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('members.index')->with('success', 'Profil Anda berhasil diperbarui.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'password' => 'required|string|min:6',
            'plan' => 'nullable|string|max:50',
            'plan_price' => 'nullable|integer|min:0',
            'subscribed_until' => 'nullable|date',
        ], [
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah dipakai',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, underscore',
            'password.min' => 'Password minimal 6 karakter',
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
        $validated = $request->validate(['password' => 'required|string|min:6'], [
            'password.min' => 'Password minimal 6 karakter',
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
        $user->delete();
        return redirect()->route('members.index')->with('success', 'Member dihapus.');
    }

    /** Cegah super admin lain diubah/dihapus dari sini. */
    private function guardSuperAdmin(User $user): void
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat dikelola sebagai member.');
        }
    }
}
