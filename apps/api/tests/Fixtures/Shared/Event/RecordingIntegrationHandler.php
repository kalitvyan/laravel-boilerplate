<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Event;

use Illuminate\Support\Facades\Context;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventEnvelope;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventHandler;

final class RecordingIntegrationHandler implements IntegrationEventHandler
{
    /** @var list<string> */
    public static array $received = [];

    /** @var list<mixed> */
    public static array $traceIds = [];

    public function __invoke(IntegrationEventEnvelope $envelope): void
    {
        self::$received[] = $envelope->messageId;
        self::$traceIds[] = Context::get('trace_id');
    }
}
