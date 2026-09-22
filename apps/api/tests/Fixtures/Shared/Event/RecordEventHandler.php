<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Event;

use LaravelBoilerplate\Shared\Application\Event\EventCollector;
use RuntimeException;

final readonly class RecordEventHandler
{
    public function __construct(private EventCollector $collector) {}

    public function __invoke(RecordEventCommand $command): void
    {
        // Имитируем репозиторий: агрегат сохранён, события переданы коллектору
        $this->collector->collect(new ThingHappened($command->thingId));

        if ($command->fail) {
            throw new RuntimeException('boom');
        }
    }
}
