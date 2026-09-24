<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\API\Trace\Span;

final readonly class TraceContextProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $spanContext = Span::getCurrent()->getContext();

        if (! $spanContext->isValid()) {
            return $record;
        }

        // Поля именно так: по ним Grafana связывает Loki и Tempo
        return $record->with(extra: [
            ...$record->extra,
            'trace_id' => $spanContext->getTraceId(),
            'span_id' => $spanContext->getSpanId(),
        ]);
    }
}
