<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelBoilerplate\Shared\Application\Exception\AccessDenied;
use LaravelBoilerplate\Shared\Application\Exception\InvalidInput;
use LaravelBoilerplate\Shared\Application\Exception\NotFound;
use LaravelBoilerplate\Shared\Domain\Exception\DomainError;
use LaravelBoilerplate\Shared\Presentation\Http\Middleware\AssignRequestId;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemRenderer;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Нарушения бизнес-правил и клиентские ошибки — не инциденты
        $exceptions->dontReport([
            DomainError::class,
            NotFound::class,
            AccessDenied::class,
            InvalidInput::class,
        ]);

        $exceptions->render(
            static fn (Throwable $e, Request $request): ?JsonResponse => app(ProblemRenderer::class)->render($e, $request),
        );
    })
    ->create();
