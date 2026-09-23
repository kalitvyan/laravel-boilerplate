<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Contract\Event;

use DateTimeImmutable;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEvent;

/**
 * Публичный контракт. Поля можно только добавлять; несовместимое изменение — это V2.
 */
final readonly class UserRegisteredV1 implements IntegrationEvent
{
    public const string NAME = 'identity.user_registered';

    public function __construct(
        private string $userId,
        private string $email,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function eventName(): string
    {
        return self::NAME;
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function aggregateType(): string
    {
        return 'identity.user';
    }

    public function aggregateId(): string
    {
        return $this->userId;
    }

    public function payload(): array
    {
        return ['userId' => $this->userId, 'email' => $this->email];
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
