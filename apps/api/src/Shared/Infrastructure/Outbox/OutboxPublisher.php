<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox;

use Illuminate\Database\ConnectionResolverInterface;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEvent;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventPublisher;
use LaravelBoilerplate\Shared\Application\Event\MessageMetadata;
use LogicException;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

final readonly class OutboxPublisher implements IntegrationEventPublisher
{
    private const string TIMESTAMP_FORMAT = 'Y-m-d H:i:s.uP';

    public function __construct(
        private ConnectionResolverInterface $db,
        private MessageMetadata $metadata,
        private ClockInterface $clock,
    ) {}

    public function publish(IntegrationEvent ...$events): void
    {
        if ($events === []) {
            return;
        }

        $connection = $this->db->connection();

        // Главный инвариант паттерна: запись в outbox обязана быть частью транзакции команды
        if ($connection->transactionLevel() === 0) {
            throw new LogicException('Integration events must be published inside a transaction');
        }

        $metadata = json_encode($this->metadata->current(), JSON_THROW_ON_ERROR);
        $now = $this->clock->now()->format(self::TIMESTAMP_FORMAT);

        $rows = array_map(
            static fn (IntegrationEvent $event): array => [
                'id' => Uuid::v7()->toRfc4122(),
                'event_name' => $event->eventName(),
                'event_version' => $event->eventVersion(),
                'aggregate_type' => $event->aggregateType(),
                'aggregate_id' => $event->aggregateId(),
                'payload' => json_encode($event->payload(), JSON_THROW_ON_ERROR),
                'metadata' => $metadata,
                'occurred_at' => $event->occurredAt()->format(self::TIMESTAMP_FORMAT),
                'created_at' => $now,
            ],
            $events,
        );

        $connection->table(OutboxTable::NAME)->insert($rows);
    }
}
