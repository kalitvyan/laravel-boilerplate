<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Event;

use Illuminate\Contracts\Container\Container;
use LaravelBoilerplate\Shared\Application\Event\DomainEventDispatcher;
use LaravelBoilerplate\Shared\Application\Event\DomainEventListener;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;
use LogicException;

final readonly class MappedDomainEventDispatcher implements DomainEventDispatcher
{
    public function __construct(
        private Container $container,
        private DomainEventListenerMap $listeners,
    ) {}

    public function dispatch(DomainEvent $event): void
    {
        foreach ($this->listeners->listenersFor($event) as $listenerClass) {
            $listener = $this->container->make($listenerClass);

            if (! $listener instanceof DomainEventListener) {
                throw new LogicException(sprintf('%s must implement %s', $listenerClass, DomainEventListener::class));
            }

            $listener($event);
        }
    }
}
