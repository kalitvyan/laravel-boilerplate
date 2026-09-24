<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus\Middleware;

use LaravelBoilerplate\Shared\Application\Bus\Command;
use LaravelBoilerplate\Shared\Application\Bus\CommandMiddleware;
use LaravelBoilerplate\Shared\Application\Metrics\MetricName;
use LaravelBoilerplate\Shared\Application\Metrics\Metrics;
use Throwable;

final readonly class MeasureCommandMiddleware implements CommandMiddleware
{
    public function __construct(private Metrics $metrics) {}

    public function handle(Command $command, callable $next): void
    {
        $startedAt = hrtime(true);
        $outcome = 'success';

        try {
            $next($command);
        } catch (Throwable $e) {
            $outcome = 'failure';

            throw $e;
        } finally {
            $this->metrics->record(
                MetricName::COMMAND_DURATION,
                (hrtime(true) - $startedAt) / 1_000_000_000,
                ['command' => $this->shortName($command::class), 'outcome' => $outcome],
            );
        }
    }

    private function shortName(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
