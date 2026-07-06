<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\CreateTokenController;
use App\Http\Controllers\Api\Auth\RegisterUserController;
use App\Http\Controllers\Api\Auth\RevokeTokenController;
use App\Http\Controllers\Api\Blog\CommentController;
use App\Http\Controllers\Api\Blog\PostController;
use Illuminate\Support\Facades\Route;

Route::post('/sanctum/token', CreateTokenController::class);
Route::post('/users', RegisterUserController::class);

Route::prefix('blog')->group(function (): void {
    Route::apiResource('posts', PostController::class)
        ->scoped(['post' => 'slug'])
        ->only(['index', 'show']);
    Route::apiResource('comments', CommentController::class)->only(['index', 'show']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::delete('/sanctum/tokens/current', RevokeTokenController::class);

    Route::prefix('blog')->group(function (): void {
        Route::apiResource('posts', PostController::class)->except(['index', 'show']);
        Route::apiResource('comments', CommentController::class)->except(['index', 'show']);
    });
});
