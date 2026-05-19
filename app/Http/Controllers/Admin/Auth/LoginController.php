<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('admin.auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('admin.invalid_credentials'),
            ]);
        }

        if (! $request->user()->is_active) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'This account is inactive. Please contact the main admin.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        if ($user->isSubadmin()) {
            $fallback = $user->canManageNews()
                ? route('admin.news.index')
                : ($user->canCustomizeSite() ? route('admin.settings.edit') : route('admin.login'));
        } else {
            $fallback = route('admin.dashboard');
        }

        return redirect()->intended($fallback);
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
