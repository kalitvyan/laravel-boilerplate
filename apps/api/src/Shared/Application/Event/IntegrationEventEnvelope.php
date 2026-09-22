<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * То, что получает консьюмер: событие плюс идентификатор сообщения и сквозные метаданные.
 */
final readonly class IntegrationEventEnvelope
{
    public const string DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $messageId,
        public string $eventName,
        public int $eventVersion,
        public ?string $aggregateType,
        public ?string $aggregateId,
        public array $payload,
        public array $metadata,
        public DateTimeImmutable $occurredAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'messageId' => $this->messageId,
            'eventName' => $this->eventName,
            'eventVersion' => $this->eventVersion,
            'aggregateType' => $this->aggregateType,
            'aggregateId' => $this->aggregateId,
            'payload' => $this->payload,
            'metadata' => $this->metadata,
            'occurredAt' => $this->occurredAt->format(self::DATE_FORMAT),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $messageId = $data['messageId'] ?? null;
        $eventName = $data['eventName'] ?? null;
        $eventVersion = $data['eventVersion'] ?? null;
        $aggregateType = $data['aggregateType'] ?? null;
        $aggregateId = $data['aggregateId'] ?? null;
        $payload = $data['payload'] ?? null;
        $metadata = $data['metadata'] ?? null;
        $occurredAt = $data['occurredAt'] ?? null;

        if (
            ! is_string($messageId)
            || ! is_string($eventName)
            || ! is_int($eventVersion)
            || ($aggregateType !== null && ! is_string($aggregateType))
            || ($aggregateId !== null && ! is_string($aggregateId))
            || ! is_array($payload)
            || ! is_array($metadata)
            || ! is_string($occurredAt)
        ) {
            throw new InvalidArgumentException('Malformed integration event envelope');
        }

        $parsedOccurredAt = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $occurredAt);

        if ($parsedOccurredAt === false) {
            throw new InvalidArgumentException('Malformed integration event envelope: occurredAt');
        }

        /** @var array<string, mixed> $payload */
        /** @var array<string, mixed> $metadata */
        return new self(
            $messageId,
            $eventName,
            $eventVersion,
            $aggregateType,
            $aggregateId,
            $payload,
            $metadata,
            $parsedOccurredAt,
        );
    }
}
