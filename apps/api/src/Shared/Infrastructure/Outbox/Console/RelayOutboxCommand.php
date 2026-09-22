<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxRelay;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\PostgresNotificationListener;
use Psr\Log\LoggerInterface;
use Throwable;

#[Description('Relay transactional outbox messages to the queue')]
#[Signature('outbox:relay
        {--batch=100 : Messages per transaction}
        {--idle=5000 : Max wait for a notification between empty batches, ms}
        {--max-runtime=3600 : Exit after N seconds so the orchestrator restarts the process}
        {--memory=128 : Exit when memory usage exceeds N MB}
        {--once : Relay until the outbox is empty, then exit}')]
final class RelayOutboxCommand extends Command
{
    private bool $shouldStop = false;

    public function handle(OutboxRelay $relay, PostgresNotificationListener $listener, LoggerInterface $logger): int
    {
        $batch = $this->positiveIntOption('batch');
        $idle = $this->positiveIntOption('idle');
        $maxRuntime = $this->positiveIntOption('max-runtime');
        $memoryLimit = $this->positiveIntOption('memory') * 1024 * 1024;
        $once = $this->option('once') === true;

        // Graceful shutdown: текущий батч дорабатывается, новый не начинается
        $this->trap([SIGTERM, SIGINT, SIGQUIT], function (): void {
            $this->shouldStop = true;
        });

        if (! $once) {
            $listener->listen(OutboxRelay::CHANNEL);
        }

        $startedAt = time();
        $backoff = 0;

        while (! $this->shouldStop) {
            try {
                $relayed = $relay->relayBatch($batch);
                $backoff = 0;
            } catch (Throwable $e) {
                if ($once) {
                    throw $e;
                }

                $logger->error('Outbox relay batch failed', ['exception' => $e]);
                $backoff = min(max($backoff * 2, 1), 30);
                sleep($backoff);

                continue;
            }

            if (time() - $startedAt >= $maxRuntime || memory_get_usage(true) >= $memoryLimit) {
                break;
            }

            // Полный батч — вероятно, есть ещё сообщения, ждать не нужно
            if ($relayed === $batch) {
                continue;
            }

            if ($once) {
                break;
            }

            $listener->wait($idle);
        }

        return self::SUCCESS;
    }

    private function positiveIntOption(string $name): int
    {
        $value = $this->option($name);

        if (! is_string($value) || ! ctype_digit($value) || (int) $value < 1) {
            throw new InvalidArgumentException(sprintf('Option --%s must be a positive integer', $name));
        }

        return (int) $value;
    }
}
