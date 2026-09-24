<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelBoilerplate\Shared\Application\Metrics\MetricName;
use LaravelBoilerplate\Shared\Application\Metrics\Metrics;
use Tests\Fixtures\Shared\RecordingMetrics;

beforeEach(function (): void {
    $this->withoutContractValidation();
    $this->metrics = new RecordingMetrics;
    $this->app->instance(Metrics::class, $this->metrics);

    Route::get('/_test/metrics/{id}', static fn (): array => ['ok' => true]);
});

it('records duration with the route template, not the url', function (): void {
    $this->getJson('/_test/metrics/0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d')->assertOk();

    $recorded = $this->metrics->named(MetricName::HTTP_SERVER_DURATION);

    expect($recorded)->toHaveCount(1)
        ->and($recorded[0][3]['http.route'])->toBe('/_test/metrics/{id}')
        ->and($recorded[0][3]['http.request.method'])->toBe('GET')
        ->and($recorded[0][3]['http.response.status_code'])->toBe(200)
        ->and($recorded[0][2])->toBeFloat();
});

it('labels unmatched routes without leaking the path', function (): void {
    $this->getJson('/definitely-missing-path')->assertNotFound();

    $recorded = $this->metrics->named(MetricName::HTTP_SERVER_DURATION);

    expect($recorded[0][3]['http.route'])->toBe('unmatched');
});

it('does not measure health probes', function (): void {
    $this->getJson('/health/live')->assertOk();

    expect($this->metrics->named(MetricName::HTTP_SERVER_DURATION))->toBe([]);
});
