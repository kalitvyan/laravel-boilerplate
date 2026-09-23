<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventEnvelope;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventHandler;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Timestamp;
use LogicException;
use Psr\Clock\ClockInterface;

#[Timeout(60)]
#[Tries(10)]
final class HandleIntegrationEvent implements ShouldQueue
{
    use Queueable;

    public const string QUEUE = 'integration';

    /**
     * @param  array<string, mixed>  $envelope  примитивный массив: payload джоба стабилен и не зависит от классов
     * @param  class-string<IntegrationEventHandler>  $handler
     */
    public function __construct(
        public readonly array $envelope,
        public readonly string $handler,
    ) {
        $this->onQueue(self::QUEUE);
        // Пушим до коммита транзакции relay: при падении коммита будет дубль, но не потеря
        $this->beforeCommit();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [1, 5, 30, 120, 600];
    }

    public function handle(
        Container $container,
        TransactionManager $transactions,
        ConnectionResolverInterface $db,
        ContextRepository $context,
        ClockInterface $clock,
    ): void {
        $envelope = IntegrationEventEnvelope::fromArray($this->envelope);

        $traceId = $envelope->metadata['trace_id'] ?? null;

        if (is_string($traceId)) {
            $context->add('trace_id', $traceId);
        }

        $handler = $container->make($this->handler);

        if (! $handler instanceof IntegrationEventHandler) {
            throw new LogicException(sprintf('%s must implement %s', $this->handler, IntegrationEventHandler::class));
        }

        $transactions->transactional(function () use ($db, $clock, $envelope, $handler): void {
            $inserted = $db->connection()->table(InboxTable::NAME)->insertOrIgnore([
                'message_id' => $envelope->messageId,
                'handler' => $this->handler,
                'processed_at' => $clock->now()->format(Timestamp::FORMAT),
            ]);

            if ($inserted === 0) {
                return; // уже обработано этим хендлером
            }

            $handler($envelope);
        });
    }
}
