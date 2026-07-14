<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\User;

use App\Data\Auth\RegisterData;
use App\Data\Auth\UpdateUserData;
use App\Enums\RoleLabel;
use App\Http\Controllers\Admin\Controller;
use App\Models\User;
use App\Services\Auth\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): View
    {
        $this->authorizeAdmin();

        return view('admin.users.index', [
            'users' => User::orderByDesc('created_at')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.users.form', [
            'user' => new User,
            'roleLabels' => RoleLabel::cases(),
        ]);
    }

    public function store(RegisterData $registerData): RedirectResponse
    {
        $this->authorizeAdmin();

        $this->userService->createWith($registerData, $registerData->role_label ?? RoleLabel::user);

        return redirect()->route('blade.admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorizeAdmin();

        return view('admin.users.form', [
            'user' => $user,
            'roleLabels' => RoleLabel::cases(),
        ]);
    }

    public function update(UpdateUserData $updateUserData, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $this->userService->updateWith($user, $updateUserData);

        return redirect()->route('blade.admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $user->delete();

        return redirect()->route('blade.admin.users.index')
            ->with('status', 'User deleted successfully.');
    }
}
