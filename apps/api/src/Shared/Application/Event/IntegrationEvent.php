<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

use DateTimeImmutable;

/**
 * Событие, покидающее контекст. Имя и payload — публичный контракт,
 * менять их можно только выпуском новой версии события.
 */
interface IntegrationEvent
{
    /**
     * Точечная нотация: identity.user_registered
     */
    public function eventName(): string;

    public function eventVersion(): int;

    public function aggregateType(): ?string;

    public function aggregateId(): ?string;

    /**
     * @return array<string, mixed> должен быть JSON-сериализуемым
     */
    public function payload(): array;

    public function occurredAt(): DateTimeImmutable;
}
