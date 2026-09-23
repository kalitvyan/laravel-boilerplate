<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Event;

use LaravelBoilerplate\Identity\Contract\Event\UserRegisteredV1;
use LaravelBoilerplate\Identity\Domain\User\Event\UserRegistered;
use LaravelBoilerplate\Shared\Application\Event\DomainEventTranslator;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEvent;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;
use LogicException;

final readonly class UserRegisteredTranslator implements DomainEventTranslator
{
    public function __invoke(DomainEvent $event): IntegrationEvent
    {
        if (! $event instanceof UserRegistered) {
            throw new LogicException(sprintf('%s cannot translate %s', self::class, $event::class));
        }

        return new UserRegisteredV1(
            $event->userId->toString(),
            $event->email->toString(),
            $event->occurredAt(),
        );
    }
}
