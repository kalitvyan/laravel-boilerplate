<?php

declare(strict_types=1);

use LaravelBoilerplate\Shared\Application\Event\IntegrationEventEnvelope;

it('round-trips through array', function (): void {
    $envelope = new IntegrationEventEnvelope(
        messageId: '0191f2a0-0000-7000-8000-000000000010',
        eventName: 'testing.thing_happened',
        eventVersion: 1,
        aggregateType: 'testing.thing',
        aggregateId: '0191f2a0-0000-7000-8000-000000000011',
        payload: ['thingId' => 'x'],
        metadata: ['trace_id' => 't'],
        occurredAt: new DateTimeImmutable('2026-01-01T10:00:00.123456+00:00'),
    );

    $restored = IntegrationEventEnvelope::fromArray($envelope->toArray());

    expect($restored->toArray())->toBe($envelope->toArray());
});

it('rejects malformed envelopes', function (array $data): void {
    IntegrationEventEnvelope::fromArray($data);
})->with([
    'empty' => [[]],
    'payload is not an object' => [[
        'messageId' => 'id', 'eventName' => 'n', 'eventVersion' => 1, 'aggregateType' => null,
        'aggregateId' => null, 'payload' => 'x', 'metadata' => [], 'occurredAt' => '2026-01-01T00:00:00.000000+00:00',
    ]],
    'bad date' => [[
        'messageId' => 'id', 'eventName' => 'n', 'eventVersion' => 1, 'aggregateType' => null,
        'aggregateId' => null, 'payload' => [], 'metadata' => [], 'occurredAt' => 'yesterday',
    ]],
])->throws(InvalidArgumentException::class);
