<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'password' => 'required|string|min:8',
        ], [
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah dipakai',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan underscore',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        User::create([
            'name' => trim($validated['name'] ?? '') ?: null,
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => 'member',
            'is_active' => true,
        ]);

        return redirect()->route('settings.index')->with('success', 'User berhasil ditambahkan.')->withFragment('users');
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ], [
            'password.required' => 'Password baru wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('settings.index')->with('success', 'Password user diperbarui.')->withFragment('users');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menghapus akun yang sedang login.');
        }
        if (User::count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu user.');
        }
        $user->delete();

        return redirect()->route('settings.index')->with('success', 'User dihapus.')->withFragment('users');
    }
}
