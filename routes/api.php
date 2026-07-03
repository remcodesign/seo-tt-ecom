<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\CreateTokenController;
use App\Http\Controllers\Api\Auth\RegisterUserController;
use App\Http\Controllers\Api\Auth\RevokeTokenController;
use App\Http\Controllers\Api\Blog\PostController;
use Illuminate\Support\Facades\Route;

Route::post('/sanctum/token', CreateTokenController::class);
Route::post('/users', RegisterUserController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::delete('/sanctum/tokens/current', RevokeTokenController::class);
    Route::apiResource('posts', PostController::class);
});
