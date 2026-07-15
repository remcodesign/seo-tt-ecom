<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Data\Auth\RegisterData;
use App\Data\Auth\UpdateUserData;
use App\Enums\RoleLabel;
use App\Models\User;
use App\Services\Auth\UserService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.admin')]
class Form extends Component
{
    public UserForm $form;

    public function mount(?User $user = null): void
    {
        if ($user instanceof User && $user->exists) {
            $this->form->setUser($user);
        }
    }

    public function save(UserService $userService): void
    {
        // Update of an existing user
        if ($this->form->user?->exists) {
            $updateData = UpdateUserData::validateAndCreate([
                'id' => $this->form->user->id,
                'name' => $this->form->name,
                'email' => $this->form->email,
                'role_label' => RoleLabel::from($this->form->role_label),
                'password' => $this->form->password ?: null,
                'password_confirmation' => $this->form->password_confirmation ?: null,
            ]);

            $userService->updateWith($this->form->user, $updateData);

            session()->flash('status', 'User updated successfully.');
            $this->redirectRoute('admin.users.index');

            return;
        }

        // Create a new user
        $registerData = RegisterData::validateAndCreate([
            'name' => $this->form->name,
            'email' => $this->form->email,
            'password' => $this->form->password,
            'password_confirmation' => $this->form->password_confirmation,
            'role_label' => $this->form->role_label !== '' && $this->form->role_label !== '0'
                ? RoleLabel::from($this->form->role_label)
                : RoleLabel::user,
        ]);

        $userService->createWith($registerData, $registerData->role_label ?? RoleLabel::user);

        session()->flash('status', 'User created successfully.');
        $this->redirectRoute('admin.users.index');
    }

    public function render(): View
    {
        return view('livewire.admin.users.form', [
            'roleLabels' => collect(RoleLabel::cases())
                ->reject(static fn (RoleLabel $roleLabel): bool => $roleLabel->value === 'guest')
                ->all(),
        ]);
    }
}
