<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Shared\Application\Metrics\MetricName;
use LaravelBoilerplate\Shared\Application\Metrics\Metrics;
use Symfony\Component\Uid\Uuid;
use Tests\Fixtures\Shared\RecordingMetrics;

beforeEach(function (): void {
    $this->metrics = new RecordingMetrics;
    $this->app->instance(Metrics::class, $this->metrics);
});

it('reports zero lag on an empty outbox', function (): void {
    $this->artisan('telemetry:collect')->assertSuccessful();

    expect($this->metrics->named(MetricName::OUTBOX_LAG)[0][2])->toBe(0.0)
        ->and($this->metrics->named(MetricName::OUTBOX_PENDING)[0][2])->toBe(0.0);
});

it('reports the age of the oldest unpublished message', function (): void {
    DB::table('outbox_messages')->insert([
        'id' => Uuid::v7()->toRfc4122(),
        'event_name' => 'testing.old',
        'event_version' => 1,
        'payload' => '{}',
        'metadata' => '{}',
        'occurred_at' => now()->subMinutes(5),
        'created_at' => now()->subMinutes(5),
    ]);

    $this->artisan('telemetry:collect')->assertSuccessful();

    expect($this->metrics->named(MetricName::OUTBOX_LAG)[0][2])->toBeGreaterThan(290.0)
        ->and($this->metrics->named(MetricName::OUTBOX_PENDING)[0][2])->toBe(1.0);
});

it('separates dead letters from pending messages', function (): void {
    DB::table('outbox_messages')->insert([
        'id' => Uuid::v7()->toRfc4122(),
        'event_name' => 'testing.broken',
        'event_version' => 1,
        'payload' => '{}',
        'metadata' => '{}',
        'occurred_at' => now(),
        'created_at' => now(),
        'attempts' => 10,
    ]);

    $this->artisan('telemetry:collect')->assertSuccessful();

    expect($this->metrics->named(MetricName::OUTBOX_PENDING)[0][2])->toBe(0.0)
        ->and($this->metrics->named(MetricName::OUTBOX_DEAD_LETTERED)[0][2])->toBe(1.0)
        // Сообщение в dead letter не должно раздувать лаг
        ->and($this->metrics->named(MetricName::OUTBOX_LAG)[0][2])->toBe(0.0);
});

it('samples queue depth per queue', function (): void {
    $this->artisan('telemetry:collect')->assertSuccessful();

    $queues = array_map(
        static fn (array $row): string => (string) $row[3]['queue'],
        $this->metrics->named(MetricName::QUEUE_DEPTH),
    );

    expect($queues)->toBe(['default', 'integration']);
});
