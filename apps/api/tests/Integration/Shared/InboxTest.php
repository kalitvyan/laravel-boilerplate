<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventEnvelope;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\HandleIntegrationEvent;
use Tests\Fixtures\Shared\Event\AnotherRecordingIntegrationHandler;
use Tests\Fixtures\Shared\Event\FailingIntegrationHandler;
use Tests\Fixtures\Shared\Event\RecordingIntegrationHandler;

beforeEach(function (): void {
    RecordingIntegrationHandler::$received = [];
    RecordingIntegrationHandler::$traceIds = [];
    AnotherRecordingIntegrationHandler::$received = [];

    $this->envelope = new IntegrationEventEnvelope(
        messageId: '0191f2a0-0000-7000-8000-000000000020',
        eventName: 'testing.thing_happened',
        eventVersion: 1,
        aggregateType: 'testing.thing',
        aggregateId: '0191f2a0-0000-7000-8000-000000000021',
        payload: ['thingId' => 'x'],
        metadata: ['trace_id' => '0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d'],
        occurredAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
    );
});

it('processes a redelivered message only once', function (): void {
    $job = new HandleIntegrationEvent($this->envelope->toArray(), RecordingIntegrationHandler::class);

    $this->app->call($job->handle(...));
    $this->app->call($job->handle(...));

    expect(RecordingIntegrationHandler::$received)->toBe([$this->envelope->messageId])
        ->and(DB::table('inbox_messages')->count())->toBe(1);
});

it('tracks idempotency per handler', function (): void {
    $this->app->call([new HandleIntegrationEvent($this->envelope->toArray(), RecordingIntegrationHandler::class), 'handle']);
    $this->app->call([new HandleIntegrationEvent($this->envelope->toArray(), AnotherRecordingIntegrationHandler::class), 'handle']);

    expect(RecordingIntegrationHandler::$received)->toHaveCount(1)
        ->and(AnotherRecordingIntegrationHandler::$received)->toHaveCount(1)
        ->and(DB::table('inbox_messages')->count())->toBe(2);
});

it('does not record the message when the handler fails, so a retry reprocesses it', function (): void {
    $job = new HandleIntegrationEvent($this->envelope->toArray(), FailingIntegrationHandler::class);

    expect(fn () => $this->app->call($job->handle(...)))->toThrow(RuntimeException::class, 'handler failed');

    expect(DB::table('inbox_messages')->count())->toBe(0);
});

it('restores trace id from message metadata', function (): void {
    $this->app->call([new HandleIntegrationEvent($this->envelope->toArray(), RecordingIntegrationHandler::class), 'handle']);

    expect(RecordingIntegrationHandler::$traceIds)->toBe(['0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d']);
});
