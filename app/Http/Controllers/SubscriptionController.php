<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function blocked()
    {
        $user = Auth::user();
        // Kalau tidak terblokir (super admin / member aktif), tidak perlu di sini.
        if (! $user || ! $user->isBlocked()) {
            return redirect()->route('dashboard');
        }

        return view('langganan.blocked', ['user' => $user]);
    }
}
