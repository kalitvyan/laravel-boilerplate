<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Tracing;

use LaravelBoilerplate\Shared\Application\Tracing\Tracer;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use Throwable;

final readonly class OpenTelemetryTracer implements Tracer
{
    private const string INVALID_TRACE_ID = '00000000000000000000000000000000';

    private TracerInterface $tracer;

    public function __construct(TracerProviderInterface $tracerProvider)
    {
        $this->tracer = $tracerProvider->getTracer('laravel-boilerplate');
    }

    public function span(string $name, array $attributes, callable $callback): mixed
    {
        $span = $this->tracer
            ->spanBuilder($name === '' ? 'unnamed' : $name)
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->setAttributes($attributes)
            ->startSpan();

        $scope = $span->activate();

        try {
            return $callback();
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());

            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    public function currentTraceparent(): ?string
    {
        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier);

        $traceparent = is_array($carrier) ? ($carrier['traceparent'] ?? null) : null;

        return is_string($traceparent) ? $traceparent : null;
    }

    public function currentTraceId(): ?string
    {
        $traceId = OpenTelemetrySpan::currentTraceId();

        return $traceId === self::INVALID_TRACE_ID ? null : $traceId;
    }
}
