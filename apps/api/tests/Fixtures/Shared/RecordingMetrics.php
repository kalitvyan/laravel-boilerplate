<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared;

use LaravelBoilerplate\Shared\Application\Metrics\Metrics;

final class RecordingMetrics implements Metrics
{
    /** @var list<array{string, string, float, array<string, string|int|bool>}> */
    public array $recorded = [];

    public function increment(string $name, array $attributes = [], int $by = 1): void
    {
        $this->recorded[] = ['counter', $name, (float) $by, $attributes];
    }

    public function record(string $name, float $value, array $attributes = []): void
    {
        $this->recorded[] = ['histogram', $name, $value, $attributes];
    }

    public function gauge(string $name, float $value, array $attributes = []): void
    {
        $this->recorded[] = ['gauge', $name, $value, $attributes];
    }

    /**
     * @return list<array{string, string, float, array<string, string|int|bool>}>
     */
    public function named(string $name): array
    {
        return array_values(array_filter($this->recorded, static fn (array $row): bool => $row[1] === $name));
    }
}
