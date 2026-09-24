<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Telemetry;

use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\Attributes\ServiceAttributes;
use OpenTelemetry\SemConv\Incubating\Attributes\DeploymentIncubatingAttributes;

final readonly class TelemetryResourceFactory
{
    public function __construct(
        private string $serviceName,
        private string $serviceNamespace,
        private string $serviceVersion,
        private string $environment,
    ) {}

    public function create(): ResourceInfo
    {
        return ResourceInfoFactory::defaultResource()->merge(ResourceInfo::create(Attributes::create([
            ServiceAttributes::SERVICE_NAME => $this->serviceName,
            ServiceAttributes::SERVICE_VERSION => $this->serviceVersion,
            DeploymentIncubatingAttributes::DEPLOYMENT_ENVIRONMENT_NAME => $this->environment,
            'service.namespace' => $this->serviceNamespace,
        ])));
    }
}
