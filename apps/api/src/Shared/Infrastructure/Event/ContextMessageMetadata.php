<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Event;

use Illuminate\Log\Context\Repository as ContextRepository;
use LaravelBoilerplate\Shared\Application\Event\MessageMetadata;
use LaravelBoilerplate\Shared\Application\Tracing\Tracer;

/**
 * Laravel Context живёт в пределах запроса и автоматически переносится в джобы,
 * поэтому trace id доезжает до консьюмеров без ручной передачи.
 */
final readonly class ContextMessageMetadata implements MessageMetadata
{
    public function __construct(
        private ContextRepository $context,
        private Tracer $tracer,
    ) {}

    public function current(): array
    {
        $metadata = [];

        $traceId = $this->context->get('trace_id');

        if (is_string($traceId)) {
            $metadata['trace_id'] = $traceId;
        }

        $traceparent = $this->tracer->currentTraceparent();

        if ($traceparent !== null) {
            // Консьюмер восстановит родителя и продолжит тот же трейс
            $metadata['traceparent'] = $traceparent;
        }

        return $metadata;
    }
}
