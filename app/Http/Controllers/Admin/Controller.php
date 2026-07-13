<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RoleLabel;
use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller extends BaseController
{
    protected function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }

    protected function authorizeAdmin(): void
    {
        if ($this->user()->role_label !== RoleLabel::admin) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}
