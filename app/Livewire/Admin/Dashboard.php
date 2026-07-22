<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\RoleLabel;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.admin')]
class Dashboard extends Component
{
    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user || $user->role_label !== RoleLabel::admin) {
            $this->redirectRoute('admin.login');
        }
    }

    public function render(): View
    {
        $postCategoryCount = DB::table('categorizables')
            ->where('categorizable_type', Post::class)
            ->distinct('category_id')
            ->count('category_id');

        return view('livewire.admin.dashboard', [
            'userCount' => User::count(),

            'postCategoryCount' => $postCategoryCount,
            'postCount' => Post::count(),

            'commentCount' => Comment::count(),
        ]);
    }
}
