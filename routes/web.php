<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LogoutController;
use App\Livewire\Admin\Blog\Posts\Form as LivewirePostForm;
use App\Livewire\Admin\Blog\Posts\Index as LivewirePostsIndex;
use App\Livewire\Admin\Dashboard as LivewireDashboard;
use App\Livewire\Admin\Login as LivewireLogin;
use App\Livewire\Admin\Users\Form as LivewireUserForm;
use App\Livewire\Admin\Users\Index as LivewireUsersIndex;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): Factory|View => view('welcome'));

// Route::prefix('livewire')->name('livewire.')->group(function (): void {
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::livewire('/login', LivewireLogin::class)
        ->name('login');

    Route::livewire('/', LivewireDashboard::class)
        ->name('dashboard');

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', LogoutController::class)
            ->name('logout');

        // general
        Route::prefix('users')->group(function (): void {
            Route::livewire('/', LivewireUsersIndex::class)->name('users.index');
            Route::livewire('/create', LivewireUserForm::class)->name('users.create');
            Route::livewire('/{user}/edit', LivewireUserForm::class)->name('users.edit');
        });

        // blog
        Route::prefix('blog')->name('blog.')->group(function (): void {
            Route::prefix('posts')->group(function (): void {
                Route::livewire('/', LivewirePostsIndex::class)->name('posts.index');
                Route::livewire('/create', LivewirePostForm::class)->name('posts.create');
                Route::livewire('/{post}/edit', LivewirePostForm::class)->name('posts.edit');
            });
        });
    });
});
// });

// Catch-all for Vue SPA — must be last, renders the app shell
// so Vue Router can handle the route client-side.
Route::get('/{any}', fn (): Factory|View => view('welcome'))
    ->where('any', '^(?!livewire).*$');
