<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** Maksimum percobaan login gagal sebelum dikunci sementara. */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi',
            'password.required' => 'Password wajib diisi',
        ]);

        $key = $this->throttleKey($request, $credentials['username']);

        // Sudah melewati batas percobaan -> kunci sementara.
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $remember)) {
            RateLimiter::clear($key);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Gagal -> catat percobaan (kedaluwarsa otomatis setelah DECAY_SECONDS).
        RateLimiter::hit($key, self::DECAY_SECONDS);

        return back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => 'Username atau password salah.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Halaman "lupa password": tidak ada reset mandiri (rawan pengambilalihan akun
     * karena login berbasis username tanpa verifikasi email/OTP). User diarahkan
     * menghubungi admin platform yang dapat me-reset lewat Manajemen Pengguna.
     */
    public function showForgot()
    {
        $admin = User::where('role', 'super_admin')->first();
        $adminPhone = $admin->phone ?? (Settings::get(null)['branding']['no_telp_laundry'] ?? null);

        return view('auth.forgot', ['adminPhone' => $adminPhone]);
    }

    /** Kunci throttle unik per kombinasi username + IP. */
    private function throttleKey(Request $request, string $username): string
    {
        return 'login:'.Str::lower($username).'|'.$request->ip();
    }
}
