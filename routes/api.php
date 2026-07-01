<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Blog\PostController;
use Illuminate\Support\Facades\Route;

Route::apiResource('posts', PostController::class);
