<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Tracing;

use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\Attributes\ServiceAttributes;
use OpenTelemetry\SemConv\Incubating\Attributes\DeploymentIncubatingAttributes;

final readonly class TracerProviderFactory
{
    public function __construct(
        private bool $enabled,
        private string $endpoint,
        private string $serviceName,
        private string $serviceNamespace,
        private string $serviceVersion,
        private float $sampleRatio,
        private string $environment,
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
            ->setResource($this->resource())
            ->setSampler(new ParentBased($this->sampleRatio >= 1.0
                ? new AlwaysOnSampler
                : new TraceIdRatioBasedSampler($this->sampleRatio)))
            ->build();
    }

    private function resource(): ResourceInfo
    {
        return ResourceInfoFactory::defaultResource()->merge(ResourceInfo::create(Attributes::create([
            ServiceAttributes::SERVICE_NAME => $this->serviceName,
            ServiceAttributes::SERVICE_VERSION => $this->serviceVersion,
            DeploymentIncubatingAttributes::DEPLOYMENT_ENVIRONMENT_NAME => $this->environment,
            'service.namespace' => $this->serviceNamespace,
        ])));
    }
}
