<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->needs_password_change) {
            // Allow only requests to force-change-password routes and logout
            if (!$request->routeIs('force-change-password', 'force-change-password.update', 'logout')) {
                return redirect()->route('force-change-password');
            }
        }

        return $next($request);
    }
}
