<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LogoutController;
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
    Route::get('/login', LivewireLogin::class)
        ->name('login');

    Route::get('/', LivewireDashboard::class)
        ->name('dashboard');

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', LogoutController::class)
            ->name('logout');

        Route::get('/users', LivewireUsersIndex::class)
            ->name('users.index');

        Route::get('/users/create', LivewireUserForm::class)
            ->name('users.create');

        Route::get('/users/{user}/edit', LivewireUserForm::class)
            ->name('users.edit');
    });
});
// });

// Catch-all for Vue SPA — must be last, renders the app shell
// so Vue Router can handle the route client-side.
Route::get('/{any}', fn (): Factory|View => view('welcome'))
    ->where('any', '^(?!livewire).*$');
