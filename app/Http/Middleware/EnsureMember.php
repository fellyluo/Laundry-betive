<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Halaman operasional laundry hanya untuk member (super admin diarahkan ke dashboard monitoringnya). */
class EnsureMember
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }
        return $next($request);
    }
}
