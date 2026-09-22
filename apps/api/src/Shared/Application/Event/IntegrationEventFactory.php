<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

interface IntegrationEventFactory
{
    /**
     * @return IntegrationEvent|null null, если событие не публикуется наружу
     */
    public function translate(DomainEvent $event): ?IntegrationEvent;
}
