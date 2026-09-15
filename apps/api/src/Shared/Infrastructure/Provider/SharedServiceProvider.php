<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Provider;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LaravelBoilerplate\Shared\Application\Health\HealthCheck;
use LaravelBoilerplate\Shared\Application\Health\ReadinessProbe;
use LaravelBoilerplate\Shared\Infrastructure\Health\DatabaseHealthCheck;

final class SharedServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string<HealthCheck>>
     */
    private const array READINESS_CHECKS = [
        DatabaseHealthCheck::class,
    ];

    public function register(): void
    {
        $this->app->bind(
            ReadinessProbe::class,
            static fn (Application $app): ReadinessProbe => new ReadinessProbe(
                array_map(static fn (string $class): HealthCheck => $app->make($class), self::READINESS_CHECKS),
            ),
        );
    }

    public function boot(): void
    {
        // loadRoutesFrom учитывает route:cache
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Http/routes.php');
    }
}
