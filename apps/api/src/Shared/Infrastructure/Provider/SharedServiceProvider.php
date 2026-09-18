<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Provider;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\TransactionalMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\QueryBus;
use LaravelBoilerplate\Shared\Application\Exception\AccessDenied;
use LaravelBoilerplate\Shared\Application\Exception\InvalidInput;
use LaravelBoilerplate\Shared\Application\Exception\NotFound;
use LaravelBoilerplate\Shared\Application\Health\HealthCheck;
use LaravelBoilerplate\Shared\Application\Health\ReadinessProbe;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidCursor;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidPageLimit;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use LaravelBoilerplate\Shared\Domain\Exception\InvalidIdentifier;
use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Bus\ContainerCommandBus;
use LaravelBoilerplate\Shared\Infrastructure\Bus\ContainerQueryBus;
use LaravelBoilerplate\Shared\Infrastructure\Bus\QueryHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Clock\SystemClock;
use LaravelBoilerplate\Shared\Infrastructure\Health\DatabaseHealthCheck;
use LaravelBoilerplate\Shared\Infrastructure\Transaction\DatabaseTransactionManager;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemDefinition;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemMap;
use Psr\Clock\ClockInterface;

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
        $this->app->bind(ClockInterface::class, SystemClock::class);
        $this->app->bind(TransactionManager::class, DatabaseTransactionManager::class);

        // Иммутабельные карты: заполняются при бутстрапе через extend() в провайдерах контекстов
        $this->app->singleton(CommandHandlerMap::class, static fn (): CommandHandlerMap => new CommandHandlerMap);
        $this->app->singleton(QueryHandlerMap::class, static fn (): QueryHandlerMap => new QueryHandlerMap);

        $this->app->singleton(ProblemMap::class, static fn (): ProblemMap => new ProblemMap()->with([
            NotFound::class => new ProblemDefinition(404, 'not_found', 'Resource not found'),
            AccessDenied::class => new ProblemDefinition(403, 'access_denied', 'Access denied'),
            InvalidInput::class => new ProblemDefinition(400, 'invalid_input', 'Invalid input'),
            InvalidIdentifier::class => new ProblemDefinition(400, 'invalid_identifier', 'Invalid identifier'),
            InvalidCursor::class => new ProblemDefinition(400, 'pagination.invalid_cursor', 'Invalid cursor'),
            InvalidPageLimit::class => new ProblemDefinition(400, 'pagination.invalid_limit', 'Invalid page limit'),
        ]));

        // scoped: новый экземпляр на запрос или джоб в Octane
        $this->app->scoped(CommandBus::class, static fn (Application $app): CommandBus => new ContainerCommandBus(
            $app,
            $app->make(CommandHandlerMap::class),
            [
                $app->make(TransactionalMiddleware::class),
            ],
        ));

        $this->app->scoped(QueryBus::class, static fn (Application $app): QueryBus => new ContainerQueryBus(
            $app,
            $app->make(QueryHandlerMap::class),
        ));

        $this->app->bind(
            ReadinessProbe::class,
            static fn (Application $app): ReadinessProbe => new ReadinessProbe(
                array_map(static fn (string $class): HealthCheck => $app->make($class), self::READINESS_CHECKS),
            ),
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Http/routes.php');
    }
}
