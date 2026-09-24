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
use LaravelBoilerplate\Shared\Application\Metrics\MetricName;
use LaravelBoilerplate\Shared\Application\Metrics\Metrics;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Timestamp;
use LogicException;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Context\Context;
use Psr\Clock\ClockInterface;
use Throwable;

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
        TracerProviderInterface $tracerProvider,
        Metrics $metrics,
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

        $traceparent = $envelope->metadata['traceparent'] ?? null;

        // Родитель из сообщения: трейс продолжается от запроса, породившего событие
        $parent = is_string($traceparent)
            ? TraceContextPropagator::getInstance()->extract(['traceparent' => $traceparent])
            : Context::getCurrent();

        $span = $tracerProvider->getTracer('laravel-boilerplate')
            ->spanBuilder(sprintf('consume %s', $envelope->eventName))
            ->setParent($parent)
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setAttributes([
                'messaging.message.id' => $envelope->messageId,
                'messaging.destination.name' => self::QUEUE,
                'messaging.operation.name' => 'process',
                'code.namespace' => $this->handler,
            ])
            ->startSpan();

        $scope = $span->activate();
        $startedAt = hrtime(true);
        $outcome = 'success';

        try {
            $transactions->transactional(function () use ($db, $clock, $envelope, $handler, $span, $metrics, &$outcome): void {
                $inserted = $db->connection()->table(InboxTable::NAME)->insertOrIgnore([
                    'message_id' => $envelope->messageId,
                    'handler' => $this->handler,
                    'processed_at' => Timestamp::format($clock->now()),
                ]);

                if ($inserted === 0) {
                    // Повторная доставка: эффекты в БД уже применены
                    $span->setAttribute('messaging.duplicate', true);
                    $metrics->increment(MetricName::MESSAGE_DUPLICATES, ['event' => $envelope->eventName]);
                    $outcome = 'duplicate';

                    return;
                }

                $handler($envelope);
            });
        } catch (Throwable $e) {
            $outcome = 'failure';
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());

            throw $e;
        } finally {
            $metrics->record(
                MetricName::MESSAGING_PROCESS_DURATION,
                (hrtime(true) - $startedAt) / 1_000_000_000,
                ['event' => $envelope->eventName, 'outcome' => $outcome],
            );

            $scope->detach();
            $span->end();
        }
    }
}
