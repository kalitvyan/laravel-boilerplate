<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

interface IntegrationEventPublisher
{
    public function publish(IntegrationEvent ...$events): void;
}
