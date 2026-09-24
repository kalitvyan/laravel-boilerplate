<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Tracing;

use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;

/**
 * В PHP нет фонового потока экспорта: в worker mode спаны ждали бы заполнения буфера.
 */
final readonly class FlushTraces
{
    public function __construct(private TracerProviderInterface $tracerProvider) {}

    public function __invoke(): void
    {
        if ($this->tracerProvider instanceof TracerProvider) {
            $this->tracerProvider->forceFlush();
        }
    }
}
