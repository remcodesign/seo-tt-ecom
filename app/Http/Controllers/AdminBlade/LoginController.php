<?php

declare(strict_types=1);

namespace App\Http\Controllers\AdminBlade;

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->role_label === RoleLabel::admin) {
            return redirect()->route('blade.admin.dashboard');
        }

        return view('admin_blade.auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->string('email'))->first();
        $password = $request->string('password')->toString();

        if (! $user || $user->role_label !== RoleLabel::admin || ! Hash::check($password, $user->password)) {
            return redirect()->route('blade.admin.login')
                ->withErrors(['email' => 'The provided credentials are incorrect.'])
                ->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('blade.admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('blade.admin.login');
    }
}
