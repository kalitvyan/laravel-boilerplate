<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use LaravelBoilerplate\Shared\Infrastructure\Event\IntegrationEventSubscriberMap;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\HandleIntegrationEvent;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxRelay;
use Symfony\Component\Uid\Uuid;
use Tests\Fixtures\Shared\Event\AnotherRecordingIntegrationHandler;
use Tests\Fixtures\Shared\Event\RecordingIntegrationHandler;

$insertMessage = static function (array $overrides = []): string {
    $id = Uuid::v7()->toRfc4122();

    DB::table('outbox_messages')->insert([
        'id' => $id,
        'event_name' => 'testing.thing_happened',
        'event_version' => 1,
        'aggregate_type' => 'testing.thing',
        'aggregate_id' => Uuid::v7()->toRfc4122(),
        'payload' => '{"thingId":"x"}',
        'metadata' => '{"trace_id":"0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d"}',
        'occurred_at' => '2026-01-01 00:00:00+00',
        'created_at' => '2026-01-01 00:00:00+00',
        ...$overrides,
    ]);

    return $id;
};

beforeEach(function (): void {
    Queue::fake();

    $this->app->extend(IntegrationEventSubscriberMap::class, static fn (IntegrationEventSubscriberMap $map): IntegrationEventSubscriberMap => $map
        ->with('testing.thing_happened', 1, [RecordingIntegrationHandler::class, AnotherRecordingIntegrationHandler::class]),
    );
});

it('fans out one job per subscriber and marks the message published', function () use ($insertMessage): void {
    $id = $insertMessage();

    expect($this->app->make(OutboxRelay::class)->relayBatch(100))->toBe(1);

    Queue::assertPushedOn(HandleIntegrationEvent::QUEUE, HandleIntegrationEvent::class);
    Queue::assertPushed(HandleIntegrationEvent::class, 2);
    Queue::assertPushed(
        HandleIntegrationEvent::class,
        static fn (HandleIntegrationEvent $job): bool => $job->handler === RecordingIntegrationHandler::class
            && $job->envelope['messageId'] === $id,
    );

    expect(DB::table('outbox_messages')->where('id', $id)->value('published_at'))->not->toBeNull();
});

it('does not relay the same message twice', function () use ($insertMessage): void {
    $insertMessage();
    $relay = $this->app->make(OutboxRelay::class);

    $relay->relayBatch(100);

    expect($relay->relayBatch(100))->toBe(0);
    Queue::assertPushed(HandleIntegrationEvent::class, 2);
});

it('does not deliver to subscribers of another version', function () use ($insertMessage): void {
    $insertMessage(['event_version' => 2]);

    $this->app->make(OutboxRelay::class)->relayBatch(100);

    Queue::assertNothingPushed();
});

it('marks messages without subscribers as published', function () use ($insertMessage): void {
    $id = $insertMessage(['event_name' => 'testing.nobody_listens']);

    expect($this->app->make(OutboxRelay::class)->relayBatch(100))->toBe(1);

    Queue::assertNothingPushed();
    expect(DB::table('outbox_messages')->where('id', $id)->value('published_at'))->not->toBeNull();
});

it('moves malformed messages to dead letter without blocking others', function () use ($insertMessage): void {
    $broken = $insertMessage(['payload' => '"not-an-object"']);
    $healthy = $insertMessage();
    $relay = $this->app->make(OutboxRelay::class);

    $relay->relayBatch(100);

    $row = DB::table('outbox_messages')->where('id', $broken)->sole();

    expect($row->published_at)->toBeNull()
        ->and($row->attempts)->toBe(OutboxRelay::DEAD_LETTER_ATTEMPTS)
        ->and($row->last_error)->toContain('outbox_messages.payload')
        ->and(DB::table('outbox_messages')->where('id', $healthy)->value('published_at'))->not->toBeNull()
        ->and($relay->relayBatch(100))->toBe(0);
});

it('relays in insertion order', function () use ($insertMessage): void {
    $first = $insertMessage();
    $second = $insertMessage();
    $insertMessage();

    $this->app->make(OutboxRelay::class)->relayBatch(2);

    $published = DB::table('outbox_messages')->whereNotNull('published_at')->orderBy('sequence')->pluck('id')->all();

    expect($published)->toBe([$first, $second]);
});

it('runs as a command until the outbox is empty', function () use ($insertMessage): void {
    $insertMessage();
    $insertMessage();

    $this->artisan('outbox:relay', ['--once' => true, '--batch' => '1'])->assertSuccessful();

    Queue::assertPushed(HandleIntegrationEvent::class, 4);
    expect(DB::table('outbox_messages')->whereNull('published_at')->count())->toBe(0);
});
