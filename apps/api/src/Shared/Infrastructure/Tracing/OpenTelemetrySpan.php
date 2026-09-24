<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Tracing;

use OpenTelemetry\API\Trace\Span;

final readonly class OpenTelemetrySpan
{
    public static function currentTraceId(): string
    {
        return Span::getCurrent()->getContext()->getTraceId();
    }
}
