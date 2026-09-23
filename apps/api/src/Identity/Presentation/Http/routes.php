<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelBoilerplate\Identity\Infrastructure\Http\ResolvePrincipal;
use LaravelBoilerplate\Identity\Presentation\Http\Controller\BlockUserController;
use LaravelBoilerplate\Identity\Presentation\Http\Controller\CurrentUserController;
use LaravelBoilerplate\Identity\Presentation\Http\Controller\RegisterController;
use LaravelBoilerplate\Identity\Presentation\Http\Controller\SessionController;

Route::prefix('v1')->group(function (): void {
    Route::post('/users', RegisterController::class)->name('users.register');

    Route::post('/auth/login', [SessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::post('/auth/refresh', [SessionController::class, 'refresh'])
        ->middleware('throttle:refresh')
        ->name('auth.refresh');

    Route::middleware(['auth:sanctum', ResolvePrincipal::class])->group(function (): void {
        Route::post('/auth/logout', [SessionController::class, 'destroy'])->name('auth.logout');
        Route::get('/users/me', CurrentUserController::class)->name('users.me');
        Route::post('/users/{userId}/block', BlockUserController::class)->name('users.block');
    });
});
