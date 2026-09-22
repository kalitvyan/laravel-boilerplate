<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus\Middleware;

use LaravelBoilerplate\Shared\Application\Bus\Command;
use LaravelBoilerplate\Shared\Application\Bus\CommandMiddleware;
use LaravelBoilerplate\Shared\Application\Event\DomainEventDispatcher;
use LaravelBoilerplate\Shared\Application\Event\EventCollector;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEvent;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventFactory;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventPublisher;
use LogicException;

/**
 * Выполняется внутри транзакции: строки outbox коммитятся вместе с изменениями агрегатов.
 */
final readonly class PublishRecordedEventsMiddleware implements CommandMiddleware
{
    private const int MAX_WAVES = 10;

    public function __construct(
        private EventCollector $collector,
        private DomainEventDispatcher $dispatcher,
        private IntegrationEventFactory $factory,
        private IntegrationEventPublisher $publisher,
    ) {}

    public function handle(Command $command, callable $next): void
    {
        $next($command);

        for ($wave = 0; $wave < self::MAX_WAVES; $wave++) {
            $events = $this->collector->release();

            if ($events === []) {
                return;
            }

            $integrationEvents = [];

            foreach ($events as $event) {
                // Сначала синхронные реакции: они могут породить новые события
                $this->dispatcher->dispatch($event);

                $integrationEvent = $this->factory->translate($event);

                if ($integrationEvent instanceof IntegrationEvent) {
                    $integrationEvents[] = $integrationEvent;
                }
            }

            if ($integrationEvents !== []) {
                $this->publisher->publish(...$integrationEvents);
            }
        }

        throw new LogicException(sprintf(
            'Domain event cascade did not settle after %d waves; check listeners for a cycle',
            self::MAX_WAVES,
        ));
    }
}
