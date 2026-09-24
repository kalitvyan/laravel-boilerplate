<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Metrics;

use LaravelBoilerplate\Shared\Application\Metrics\Metrics;

final readonly class NullMetrics implements Metrics
{
    public function increment(string $name, array $attributes = [], int $by = 1): void {}

    public function record(string $name, float $value, array $attributes = []): void {}

    public function gauge(string $name, float $value, array $attributes = []): void {}
}
