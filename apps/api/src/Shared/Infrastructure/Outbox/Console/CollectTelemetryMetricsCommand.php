<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Database\ConnectionResolverInterface;
use LaravelBoilerplate\Shared\Application\Metrics\MetricName;
use LaravelBoilerplate\Shared\Application\Metrics\Metrics;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\HandleIntegrationEvent;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxRelay;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxTable;
use LaravelBoilerplate\Shared\Infrastructure\Telemetry\FlushTelemetry;

#[Description('Sample outbox and queue gauges')]
#[Signature('telemetry:collect')]
final class CollectTelemetryMetricsCommand extends Command
{
    private const array QUEUES = ['default', HandleIntegrationEvent::QUEUE];

    public function handle(
        ConnectionResolverInterface $db,
        QueueFactory $queues,
        Metrics $metrics,
        FlushTelemetry $flush,
    ): int {
        $connection = $db->connection();

        // Главная метрика схемы: ловит и упавший relay, и залипшую транзакцию
        $connection->selectOne(sprintf(
            'SELECT COALESCE(EXTRACT(EPOCH FROM (now() - MIN(created_at))), 0) AS lag FROM %s WHERE published_at IS NULL AND attempts < ?',
            OutboxTable::NAME,
        ), [OutboxRelay::DEAD_LETTER_ATTEMPTS]);

        $row = $connection->selectOne(sprintf(
            'SELECT COALESCE(EXTRACT(EPOCH FROM (now() - MIN(created_at))), 0) AS lag FROM %s WHERE published_at IS NULL AND attempts < ?',
            OutboxTable::NAME,
        ), [OutboxRelay::DEAD_LETTER_ATTEMPTS]);

        $lag = is_object($row) ? ($row->lag ?? 0) : 0;

        $metrics->gauge(MetricName::OUTBOX_LAG, is_numeric($lag) ? (float) $lag : 0.0);

        $metrics->gauge(MetricName::OUTBOX_PENDING, (float) $connection->table(OutboxTable::NAME)
            ->whereNull('published_at')
            ->where('attempts', '<', OutboxRelay::DEAD_LETTER_ATTEMPTS)
            ->count());

        $metrics->gauge(MetricName::OUTBOX_DEAD_LETTERED, (float) $connection->table(OutboxTable::NAME)
            ->whereNull('published_at')
            ->where('attempts', '>=', OutboxRelay::DEAD_LETTER_ATTEMPTS)
            ->count());

        foreach (self::QUEUES as $queue) {
            $metrics->gauge(MetricName::QUEUE_DEPTH, (float) $queues->connection()->size($queue), ['queue' => $queue]);
        }

        // Процесс короткоживущий: троттлинг здесь только помешает
        $flush->force();

        return self::SUCCESS;
    }
}
