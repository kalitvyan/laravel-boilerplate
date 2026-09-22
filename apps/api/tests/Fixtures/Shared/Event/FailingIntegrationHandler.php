<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Event;

use LaravelBoilerplate\Shared\Application\Event\IntegrationEventEnvelope;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventHandler;
use RuntimeException;

final class FailingIntegrationHandler implements IntegrationEventHandler
{
    public function __invoke(IntegrationEventEnvelope $envelope): void
    {
        throw new RuntimeException('handler failed');
    }
}
