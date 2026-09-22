<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

interface MessageMetadata
{
    /**
     * Сквозной контекст сообщения: trace id, позже — актор и traceparent.
     *
     * @return array<string, scalar>
     */
    public function current(): array;
}
