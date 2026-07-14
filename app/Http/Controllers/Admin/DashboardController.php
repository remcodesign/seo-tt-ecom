<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('blade.admin.login');
        }

        $this->authorizeAdmin();

        return view('admin.dashboard', [
            'userCount' => User::count(),
            'postCount' => Post::count(),
            'commentCount' => Comment::count(),
        ]);
    }
}
