<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelBoilerplate\Shared\Presentation\Http\Health\LivenessController;
use LaravelBoilerplate\Shared\Presentation\Http\Health\ReadinessController;

Route::get('/health/live', LivenessController::class)->name('health.live');
Route::get('/health/ready', ReadinessController::class)->name('health.ready');
