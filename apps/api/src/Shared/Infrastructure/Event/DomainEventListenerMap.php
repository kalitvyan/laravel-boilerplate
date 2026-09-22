<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Event;

use LaravelBoilerplate\Shared\Application\Event\DomainEventListener;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

/**
 * Иммутабельная карта событие => список листенеров. В отличие от HandlerMap
 * допускает несколько подписчиков на одно событие.
 */
final readonly class DomainEventListenerMap
{
    /**
     * @param  array<class-string<DomainEvent>, list<class-string<DomainEventListener>>>  $listeners
     */
    public function __construct(private array $listeners = []) {}

    /**
     * @param  array<class-string<DomainEvent>, list<class-string<DomainEventListener>>>  $listeners
     */
    public function with(array $listeners): self
    {
        $merged = $this->listeners;

        foreach ($listeners as $event => $eventListeners) {
            $merged[$event] = [...$merged[$event] ?? [], ...$eventListeners];
        }

        return new self($merged);
    }

    /**
     * @return list<class-string<DomainEventListener>>
     */
    public function listenersFor(DomainEvent $event): array
    {
        return $this->listeners[$event::class] ?? [];
    }
}
