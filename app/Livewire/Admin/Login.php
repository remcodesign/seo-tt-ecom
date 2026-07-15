<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.admin')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public function mount(): void
    {
        if (Auth::check() && Auth::user()?->role_label === RoleLabel::admin) {
            $this->redirectRoute('admin.dashboard');
        }
    }

    public function authenticate(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $this->email)->first();

        if (! $user || $user->role_label !== RoleLabel::admin || ! Hash::check($this->password, $user->password)) {
            $this->addError('email', 'The provided credentials are incorrect.');

            return;
        }

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('admin.dashboard');
    }

    public function render(): View
    {
        return view('livewire.admin.login');
    }
}
