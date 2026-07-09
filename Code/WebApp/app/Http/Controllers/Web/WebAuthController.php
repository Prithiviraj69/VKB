<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WebAuthController extends Controller
{
    // GET /login
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('admin.login');
    }

    // POST /login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors(['email' => 'Your account is deactivated.'])->withInput();
            }

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'Invalid email or password credentials.',
        ])->withInput();
    }

    // GET /logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully.');
    }

    private function redirectBasedOnRole($user)
    {
        if ($user->role === 'super-admin') {
            return redirect('/super-admin');
        } elseif (in_array($user->role, ['govt-dept-admin', 'owner-dept-admin'])) {
            return redirect('/govt-dept-admin');
        } elseif ($user->role === 'dept-verifier') {
            return redirect('/dept-verifier');
        } elseif ($user->role === 'dept-approver') {
            return redirect('/dept-approver');
        } elseif ($user->role === 'verifier') {
            return redirect('/verifier');
        } elseif ($user->role === 'approver') {
            return redirect('/approver');
        } elseif ($user->role === 'ngo') {
            return redirect('/ngo');
        } elseif ($user->role === 'ngo-verifier') {
            return redirect('/ngo-verifier');
        } elseif ($user->role === 'ngo-approver') {
            return redirect('/ngo-approver');
        } elseif ($user->role === 'employer') {
            return redirect('/employer');
        } elseif ($user->role === 'contractor') {
            return redirect('/contractor');
        }

        Auth::logout();
        return redirect('/login')->withErrors(['email' => 'Access Denied: You do not have permission to access this portal.']);
    }

    // GET /force-change-password
    public function showForceChangePassword()
    {
        if (!Auth::check() || !Auth::user()->needs_password_change) {
            return redirect('/');
        }
        return view('admin.force_change_password');
    }

    // POST /force-change-password
    public function updateForceChangePassword(Request $request)
    {
        if (!Auth::check() || !Auth::user()->needs_password_change) {
            return redirect('/');
        }

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();
        // Resolve dynamic class update safety in MongoDB Eloquent
        $user->update([
            'password' => Hash::make($request->password),
            'needs_password_change' => false,
        ]);

        return $this->redirectBasedOnRole($user)->with('success', 'Your password has been changed successfully. Welcome to Vishwakarma Bandhu Portal!');
    }
}
