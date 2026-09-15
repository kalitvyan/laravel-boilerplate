<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Health;

interface HealthCheck
{
    public function name(): string;

    public function check(): HealthStatus;
}
