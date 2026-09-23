<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Provider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\PublishRecordedEventsMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\TransactionalMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\QueryBus;
use LaravelBoilerplate\Shared\Application\Event\DomainEventDispatcher;
use LaravelBoilerplate\Shared\Application\Event\EventCollector;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventFactory;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventPublisher;
use LaravelBoilerplate\Shared\Application\Event\MessageMetadata;
use LaravelBoilerplate\Shared\Application\Exception\AccessDenied;
use LaravelBoilerplate\Shared\Application\Exception\InvalidInput;
use LaravelBoilerplate\Shared\Application\Exception\NotFound;
use LaravelBoilerplate\Shared\Application\Exception\Unauthenticated;
use LaravelBoilerplate\Shared\Application\Health\ReadinessProbe;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidCursor;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidPageLimit;
use LaravelBoilerplate\Shared\Application\Storage\FileStorage;
use LaravelBoilerplate\Shared\Application\Storage\InvalidStoragePath;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use LaravelBoilerplate\Shared\Domain\Exception\InvalidIdentifier;
use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Bus\ContainerCommandBus;
use LaravelBoilerplate\Shared\Infrastructure\Bus\ContainerQueryBus;
use LaravelBoilerplate\Shared\Infrastructure\Bus\QueryHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Clock\SystemClock;
use LaravelBoilerplate\Shared\Infrastructure\Event\ContextMessageMetadata;
use LaravelBoilerplate\Shared\Infrastructure\Event\DomainEventListenerMap;
use LaravelBoilerplate\Shared\Infrastructure\Event\DomainEventTranslatorMap;
use LaravelBoilerplate\Shared\Infrastructure\Event\InMemoryEventCollector;
use LaravelBoilerplate\Shared\Infrastructure\Event\IntegrationEventSubscriberMap;
use LaravelBoilerplate\Shared\Infrastructure\Event\MappedDomainEventDispatcher;
use LaravelBoilerplate\Shared\Infrastructure\Event\MappedIntegrationEventFactory;
use LaravelBoilerplate\Shared\Infrastructure\Health\DatabaseHealthCheck;
use LaravelBoilerplate\Shared\Infrastructure\Health\RedisHealthCheck;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\Console\PruneOutboxCommand;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\Console\RelayOutboxCommand;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxPublisher;
use LaravelBoilerplate\Shared\Infrastructure\Storage\LaravelFileStorage;
use LaravelBoilerplate\Shared\Infrastructure\Transaction\DatabaseTransactionManager;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemDefinition;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemMap;
use LogicException;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

final class SharedServiceProvider extends ServiceProvider
{
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
            InvalidStoragePath::class => new ProblemDefinition(400, 'storage.invalid_path', 'Invalid storage path'),
            Unauthenticated::class => new ProblemDefinition(401, 'unauthenticated', 'Unauthenticated'),
        ]));

        // scoped: новый экземпляр на запрос или джоб в Octane
        $this->app->scoped(CommandBus::class, static fn (Application $app): CommandBus => new ContainerCommandBus(
            $app,
            $app->make(CommandHandlerMap::class),
            [
                $app->make(TransactionalMiddleware::class),
                $app->make(PublishRecordedEventsMiddleware::class),
            ],
        ));

        $this->app->scoped(QueryBus::class, static fn (Application $app): QueryBus => new ContainerQueryBus(
            $app,
            $app->make(QueryHandlerMap::class),
        ));

        $this->app->bind(ReadinessProbe::class, static fn (Application $app): ReadinessProbe => new ReadinessProbe([
            $app->make(DatabaseHealthCheck::class),
            new RedisHealthCheck($app->make(RedisFactory::class), $app->make(LoggerInterface::class), 'default', 'redis_state'),
            new RedisHealthCheck($app->make(RedisFactory::class), $app->make(LoggerInterface::class), 'cache', 'redis_cache'),
        ]));

        $this->app->scoped(EventCollector::class, InMemoryEventCollector::class);
        $this->app->bind(MessageMetadata::class, ContextMessageMetadata::class);
        $this->app->bind(IntegrationEventPublisher::class, OutboxPublisher::class);
        $this->app->bind(DomainEventDispatcher::class, MappedDomainEventDispatcher::class);
        $this->app->bind(IntegrationEventFactory::class, MappedIntegrationEventFactory::class);

        $this->app->singleton(DomainEventListenerMap::class, static fn (): DomainEventListenerMap => new DomainEventListenerMap);
        $this->app->singleton(DomainEventTranslatorMap::class, static fn (): DomainEventTranslatorMap => new DomainEventTranslatorMap);

        $this->app->singleton(
            IntegrationEventSubscriberMap::class,
            static fn (): IntegrationEventSubscriberMap => new IntegrationEventSubscriberMap,
        );

        $this->app->bind(static function (Application $app): FileStorage {
            $filesystems = $app->make(FilesystemManager::class);

            $disk = $filesystems->disk(config()->string('filesystems.default'));
            $presignDisk = $filesystems->disk(config()->string('filesystems.presign_disk'));

            if (! $disk instanceof FilesystemAdapter || ! $presignDisk instanceof FilesystemAdapter) {
                throw new LogicException('File storage requires Flysystem-backed disks');
            }

            return new LaravelFileStorage($disk, $presignDisk, $app->make(ClockInterface::class));
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Http/routes.php');

        $this->configureTrustedProxies();
        $this->configureRateLimiting();

        if ($this->app->runningInConsole()) {
            $this->commands([
                RelayOutboxCommand::class,
                PruneOutboxCommand::class,
            ]);
        }
    }

    private function configureTrustedProxies(): void
    {
        $proxies = array_values(array_filter(
            array_map(trim(...), explode(',', config()->string('api.trusted_proxies'))),
            static fn (string $proxy): bool => $proxy !== '',
        ));

        TrustProxies::at($proxies);
        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
        );
    }

    private function configureRateLimiting(): void
    {
        // На этапе 6 ключом для аутентифицированных запросов станет principal id
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(
            config()->integer('api.rate_limit.per_minute'),
        )->by('ip:'.$request->ip()));
    }
}
