<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Event;

use LaravelBoilerplate\Identity\Contract\Event\UserBlockedV1;
use LaravelBoilerplate\Identity\Domain\User\Event\UserBlocked;
use LaravelBoilerplate\Shared\Application\Event\DomainEventTranslator;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEvent;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;
use LogicException;

final readonly class UserBlockedTranslator implements DomainEventTranslator
{
    public function __invoke(DomainEvent $event): IntegrationEvent
    {
        if (! $event instanceof UserBlocked) {
            throw new LogicException(sprintf('%s cannot translate %s', self::class, $event::class));
        }

        return new UserBlockedV1(
            $event->userId->toString(),
            $event->occurredAt(),
        );
    }
}
