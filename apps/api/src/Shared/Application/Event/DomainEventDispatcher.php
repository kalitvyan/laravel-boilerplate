<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

interface DomainEventDispatcher
{
    public function dispatch(DomainEvent $event): void;
}
