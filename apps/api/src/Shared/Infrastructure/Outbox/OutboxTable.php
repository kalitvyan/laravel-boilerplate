<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox;

final readonly class OutboxTable
{
    public const string NAME = 'outbox_messages';
}
