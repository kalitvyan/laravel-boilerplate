<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Health;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use LaravelBoilerplate\Shared\Application\Health\HealthCheck;
use LaravelBoilerplate\Shared\Application\Health\HealthStatus;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class RedisHealthCheck implements HealthCheck
{
    public function __construct(
        private RedisFactory $redis,
        private LoggerInterface $logger,
        private string $connection,
        private string $name,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function check(): HealthStatus
    {
        try {
            $this->redis->connection($this->connection)->command('ping');

            return HealthStatus::up();
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Health check failed: %s', $this->name), ['exception' => $e]);

            return HealthStatus::down();
        }
    }
}
