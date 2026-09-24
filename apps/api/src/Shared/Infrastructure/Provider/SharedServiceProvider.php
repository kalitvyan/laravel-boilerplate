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
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestTerminated;
use LaravelBoilerplate\Shared\Application\Bus\ActorContext;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\AuthorizeCommandMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\PublishRecordedEventsMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\TraceCommandMiddleware;
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
use LaravelBoilerplate\Shared\Application\Tracing\Tracer;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use LaravelBoilerplate\Shared\Domain\Access\Principal;
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
use LaravelBoilerplate\Shared\Infrastructure\Http\TraceRequest;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\Console\PruneOutboxCommand;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\Console\RelayOutboxCommand;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxPublisher;
use LaravelBoilerplate\Shared\Infrastructure\Storage\LaravelFileStorage;
use LaravelBoilerplate\Shared\Infrastructure\Tracing\FlushTraces;
use LaravelBoilerplate\Shared\Infrastructure\Tracing\OpenTelemetryTracer;
use LaravelBoilerplate\Shared\Infrastructure\Tracing\TracerProviderFactory;
use LaravelBoilerplate\Shared\Infrastructure\Transaction\DatabaseTransactionManager;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemDefinition;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemMap;
use LogicException;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

// SharedPresentation\Http\CurrentPrincipal больше не импортируется:
// провайдер читает атрибут напрямую через ActorContext::ATTRIBUTE

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ClockInterface::class, SystemClock::class);
        $this->app->bind(TransactionManager::class, DatabaseTransactionManager::class);

        $this->app->scoped(AuthorizeCommandMiddleware::class);

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
                $app->make(AuthorizeCommandMiddleware::class),
                $app->make(TraceCommandMiddleware::class),
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

        $this->app->singleton(TracerProviderInterface::class, static fn (): TracerProviderInterface => new TracerProviderFactory(
            config()->boolean('otel.enabled'),
            config()->string('otel.endpoint'),
            config()->string('otel.service.name'),
            config()->string('otel.service.namespace'),
            config()->string('otel.service.version'),
            config()->float('otel.sample_ratio'),
            config()->string('app.env'),
        )->create());

        $this->app->singleton(Tracer::class, OpenTelemetryTracer::class);

        $this->app->bind(TraceRequest::class, static fn (Application $app): TraceRequest => new TraceRequest(
            $app->make(TracerProviderInterface::class),
            config()->boolean('otel.enabled'),
        ));
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

        if (class_exists(RequestTerminated::class)) {
            Event::listen(RequestTerminated::class, FlushTraces::class);
        }

        Event::listen(JobProcessed::class, FlushTraces::class);
        Event::listen(JobFailed::class, FlushTraces::class);
        $this->app->terminating(FlushTraces::class);
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
        RateLimiter::for('api', static function (Request $request): Limit {
            $principal = $request->attributes->get(ActorContext::ATTRIBUTE);
            $perMinute = config()->integer('api.rate_limit.per_minute');

            return $principal instanceof Principal
                ? Limit::perMinute($perMinute)->by('principal:'.$principal->id)
                : Limit::perMinute($perMinute)->by('ip:'.$request->ip());
        });
    }
}
