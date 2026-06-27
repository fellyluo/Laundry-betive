<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberSignupController extends Controller
{
    /** Form daftar jadi member/pengguna aplikasi (publik, dari landing page). */
    public function show()
    {
        return view('member.signup');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:30', function ($attr, $value, $fail) {
                if (strlen(preg_replace('/[^0-9]/', '', $value)) < 8) {
                    $fail('Nomor HP tidak valid (minimal 8 angka)');
                }
            }],
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'password' => 'required|string|min:8',
        ], [
            'name.required' => 'Nama / nama usaha wajib diisi',
            'phone.required' => 'Nomor HP / WhatsApp wajib diisi',
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah dipakai, coba yang lain',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, underscore',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        // Dibuat sebagai member PENDING (belum aktif) — menunggu aktivasi Super Admin.
        $user = User::create([
            'name' => trim($validated['name']),
            'phone' => trim($validated['phone']),
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => 'member',
            'is_active' => false,
        ]);

        return view('member.signup-success', ['user' => $user]);
    }
}
