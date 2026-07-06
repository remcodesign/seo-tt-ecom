<?php

declare(strict_types=1);

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): Factory|View => view('welcome'));

// Catch-all for Vue SPA — must be last, renders the app shell
// so Vue Router can handle the route client-side.
Route::get('/{any}', fn (): Factory|View => view('welcome'))
    ->where('any', '.*');
