<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Metrics;

use LaravelBoilerplate\Shared\Infrastructure\Telemetry\TelemetryResourceFactory;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Metrics\Noop\NoopMeterProvider;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;

final readonly class MeterProviderFactory
{
    public function __construct(
        private bool $enabled,
        private string $endpoint,
        private TelemetryResourceFactory $resources,
    ) {}

    public function create(): MeterProviderInterface
    {
        if (! $this->enabled) {
            return new NoopMeterProvider;
        }

        $transport = new OtlpHttpTransportFactory()->create(
            rtrim($this->endpoint, '/').'/v1/metrics',
            'application/json',
        );

        $reader = new ExportingReader(new MetricExporter($transport, Temporality::CUMULATIVE));

        return MeterProvider::builder()
            ->setResource($this->resources->create())
            ->addReader($reader)
            ->build();
    }
}
