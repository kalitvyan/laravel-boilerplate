<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox;

final readonly class InboxTable
{
    public const string NAME = 'inbox_messages';
}
