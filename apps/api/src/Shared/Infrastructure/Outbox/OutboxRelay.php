<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox;

use DateMalformedStringException;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use InvalidArgumentException;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventEnvelope;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use LaravelBoilerplate\Shared\Infrastructure\Event\IntegrationEventSubscriberMap;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Row;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Timestamp;
use LogicException;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Throwable;

final readonly class OutboxRelay
{
    public const string CHANNEL = 'outbox_new';

    /**
     * attempts >= этого значения — dead letter, relay такие сообщения больше не выбирает.
     */
    public const int DEAD_LETTER_ATTEMPTS = 10;

    public function __construct(
        private ConnectionResolverInterface $db,
        private TransactionManager $transactions,
        private Dispatcher $bus,
        private IntegrationEventSubscriberMap $subscribers,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {}

    /**
     * Инфраструктурные сбои (Redis, БД) пробрасываются наружу, и транзакция откатывается.
     *
     * @return int сколько сообщений обработано в батче
     */
    public function relayBatch(int $limit): int
    {
        return $this->transactions->transactional(function () use ($limit): int {
            $connection = $this->db->connection();

            // SKIP LOCKED: несколько реплик relay не мешают друг другу и не публикуют одно и то же
            $rows = $connection->table(OutboxTable::NAME)
                ->whereNull('published_at')
                ->where('attempts', '<', self::DEAD_LETTER_ATTEMPTS)
                ->orderBy('sequence')
                ->limit($limit)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->get()
                ->all();

            $published = [];

            foreach ($rows as $row) {
                $sequence = $row->sequence ?? null;

                if (! is_int($sequence)) {
                    throw new LogicException('Outbox row without integer sequence');
                }

                try {
                    $envelope = $this->envelopeFrom($row);
                } catch (InvalidArgumentException|DateMalformedStringException|LogicException $e) {
                    $this->deadLetter($connection, $sequence, $e);

                    continue;
                }

                foreach ($this->subscribers->handlersFor($envelope->eventName, $envelope->eventVersion) as $handler) {
                    $this->bus->dispatch(new HandleIntegrationEvent($envelope->toArray(), $handler));
                }

                // Сообщение без подписчиков тоже считается опубликованным
                $published[] = $sequence;
            }

            if ($published !== []) {
                $connection->table(OutboxTable::NAME)
                    ->whereIn('sequence', $published)
                    ->update(['published_at' => $this->clock->now()->format(Timestamp::FORMAT)]);
            }

            return count($rows);
        });
    }

    private function envelopeFrom(stdClass $row): IntegrationEventEnvelope
    {
        $data = Row::from($row, OutboxTable::NAME);

        return IntegrationEventEnvelope::fromArray([
            'messageId' => $data->string('id'),
            'eventName' => $data->string('event_name'),
            'eventVersion' => $data->int('event_version'),
            'aggregateType' => $data->nullableString('aggregate_type'),
            'aggregateId' => $data->nullableString('aggregate_id'),
            'payload' => $data->jsonObject('payload'),
            'metadata' => $data->jsonObject('metadata'),
            'occurredAt' => $data->timestamp('occurred_at')->format(IntegrationEventEnvelope::DATE_FORMAT),
        ]);
    }

    private function deadLetter(ConnectionInterface $connection, int $sequence, Throwable $reason): void
    {
        $this->logger->error('Outbox message is malformed and moved to dead letter', [
            'sequence' => $sequence,
            'exception' => $reason,
        ]);

        $connection->table(OutboxTable::NAME)
            ->where('sequence', $sequence)
            ->update([
                'attempts' => self::DEAD_LETTER_ATTEMPTS,
                'last_error' => mb_substr($reason->getMessage(), 0, 1000),
            ]);
    }
}
