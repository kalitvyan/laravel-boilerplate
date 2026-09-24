<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Tracing;

use LaravelBoilerplate\Shared\Infrastructure\Telemetry\TelemetryResourceFactory;
use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

final readonly class TracerProviderFactory
{
    public function __construct(
        private bool $enabled,
        private string $endpoint,
        private float $sampleRatio,
        private TelemetryResourceFactory $resources,
    ) {}

    public function create(): TracerProviderInterface
    {
        if (! $this->enabled) {
            return new NoopTracerProvider;
        }

        // JSON вместо protobuf: не требует ext-protobuf в ZTS-сборке
        $transport = new OtlpHttpTransportFactory()->create(
            rtrim($this->endpoint, '/').'/v1/traces',
            'application/json',
        );

        return TracerProvider::builder()
            ->addSpanProcessor(BatchSpanProcessor::builder(new SpanExporter($transport))->build())
            ->setResource($this->resources->create())
            // ParentBased: решение о сэмплировании принимается один раз на корне трейса,
            // иначе цепочка HTTP → outbox → консьюмер рвалась бы посередине
            ->setSampler(new ParentBased($this->sampleRatio >= 1.0
                ? new AlwaysOnSampler
                : new TraceIdRatioBasedSampler($this->sampleRatio)))
            ->build();
    }
}
