<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Event;

use DateTimeImmutable;
use LaravelBoilerplate\Shared\Application\Event\DomainEventTranslator;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEvent;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

final readonly class ThingHappenedTranslator implements DomainEventTranslator
{
    public function __invoke(DomainEvent $event): IntegrationEvent
    {
        assert($event instanceof ThingHappened);

        return new readonly class($event->thingId, $event->occurredAt()) implements IntegrationEvent
        {
            public function __construct(private string $thingId, private DateTimeImmutable $occurredAt) {}

            public function eventName(): string
            {
                return 'testing.thing_happened';
            }

            public function eventVersion(): int
            {
                return 1;
            }

            public function aggregateType(): string
            {
                return 'testing.thing';
            }

            public function aggregateId(): string
            {
                return $this->thingId;
            }

            public function payload(): array
            {
                return ['thingId' => $this->thingId];
            }

            public function occurredAt(): DateTimeImmutable
            {
                return $this->occurredAt;
            }
        };
    }
}
