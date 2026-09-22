<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Event;

use Illuminate\Log\Context\Repository as ContextRepository;
use LaravelBoilerplate\Shared\Application\Event\MessageMetadata;

/**
 * Laravel Context живёт в пределах запроса и автоматически переносится в джобы,
 * поэтому trace id доезжает до консьюмеров без ручной передачи.
 */
final readonly class ContextMessageMetadata implements MessageMetadata
{
    public function __construct(private ContextRepository $context) {}

    public function current(): array
    {
        $traceId = $this->context->get('trace_id');

        return is_string($traceId) ? ['trace_id' => $traceId] : [];
    }
}
