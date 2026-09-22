<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Event;

use LaravelBoilerplate\Shared\Application\Event\IntegrationEventEnvelope;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventHandler;

final class AnotherRecordingIntegrationHandler implements IntegrationEventHandler
{
    /** @var list<string> */
    public static array $received = [];

    public function __invoke(IntegrationEventEnvelope $envelope): void
    {
        self::$received[] = $envelope->messageId;
    }
}
