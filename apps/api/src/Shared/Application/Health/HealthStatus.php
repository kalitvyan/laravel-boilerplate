<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Health;

final readonly class HealthStatus
{
    private function __construct(public bool $healthy) {}

    public static function up(): self
    {
        return new self(true);
    }

    public static function down(): self
    {
        return new self(false);
    }
}
