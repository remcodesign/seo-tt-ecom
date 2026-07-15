<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Form as LivewireForm;

class UserForm extends LivewireForm
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $role_label = 'user';

    public string $password = '';

    public string $password_confirmation = '';

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_label = $user->role_label->value;
    }
}
