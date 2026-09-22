<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Event;

use LaravelBoilerplate\Shared\Application\Event\DomainEventListener;
use LaravelBoilerplate\Shared\Application\Event\EventCollector;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

final class CascadingListener implements DomainEventListener
{
    public static int $calls = 0;

    public function __construct(private readonly EventCollector $collector) {}

    public function __invoke(DomainEvent $event): void
    {
        self::$calls++;

        // Листенер меняет другой агрегат и порождает новое событие
        $this->collector->collect(new SomethingElseHappened('second'));
    }
}
