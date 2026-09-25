<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! auth()->attempt($credentials, $request->boolean('remember'))) {
            // Log percobaan login yang gagal untuk memantau brute force
            AuditLog::catat('auth.login_failed', null, ['email' => $request->input('email')]);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Email atau password tidak valid.',
            ]);
        }

        $request->session()->regenerate();

        // Log login berhasil
        AuditLog::catat('auth.login', auth()->user());

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Log logout sebelum session dihapus
        if (auth()->check()) {
            AuditLog::catat('auth.logout', auth()->user());
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
