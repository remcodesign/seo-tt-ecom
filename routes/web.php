<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): Factory|View => view('welcome'));

Route::prefix('blade')->name('blade.')->group(function (): void {
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/login', [LoginController::class, 'showLoginForm'])
            ->name('login');
        Route::post('/login', [LoginController::class, 'authenticate'])
            ->name('authenticate');

        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::middleware('auth')->group(function (): void {
            Route::post('/logout', [LoginController::class, 'logout'])
                ->name('logout');
        });
    });
});

// Catch-all for Vue SPA — must be last, renders the app shell
// so Vue Router can handle the route client-side.
Route::get('/{any}', fn (): Factory|View => view('welcome'))
    ->where('any', '.*');
