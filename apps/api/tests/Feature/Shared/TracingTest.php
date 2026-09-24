<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

beforeEach(function (): void {
    $this->withoutContractValidation();

    $this->storage = new ArrayObject;
    $exporter = new InMemoryExporter($this->storage);

    $this->app->instance(
        TracerProviderInterface::class,
        TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($exporter))->build(),
    );

    config(['otel.enabled' => true]);

    Route::get('/_test/traced', static fn (): array => ['ok' => true]);
});

it('creates a server span named after the route template', function (): void {
    $this->getJson('/_test/traced')->assertOk();

    $spans = iterator_to_array($this->storage);

    expect($spans)->toHaveCount(1)
        ->and($spans[0]->getName())->toBe('GET /_test/traced')
        ->and($spans[0]->getAttributes()->get('http.response.status_code'))->toBe(200);
});

it('continues an incoming trace', function (): void {
    $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

    $this->withHeader('traceparent', sprintf('00-%s-00f067aa0ba902b7-01', $traceId))
        ->getJson('/_test/traced')
        ->assertOk()
        ->assertHeader('X-Request-Id', $traceId);

    $spans = iterator_to_array($this->storage);

    expect($spans[0]->getTraceId())->toBe($traceId)
        ->and($spans[0]->getParentSpanId())->toBe('00f067aa0ba902b7');
});

it('does not trace health probes', function (): void {
    $this->getJson('/health/live')->assertOk();

    expect(iterator_to_array($this->storage))->toBe([]);
});
