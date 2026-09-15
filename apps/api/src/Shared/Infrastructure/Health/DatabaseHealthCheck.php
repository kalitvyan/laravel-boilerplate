<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Health;

use Illuminate\Database\ConnectionResolverInterface;
use LaravelBoilerplate\Shared\Application\Health\HealthCheck;
use LaravelBoilerplate\Shared\Application\Health\HealthStatus;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class DatabaseHealthCheck implements HealthCheck
{
    public function __construct(
        private ConnectionResolverInterface $db,
        private LoggerInterface $logger,
    ) {}

    public function name(): string
    {
        return 'database';
    }

    public function check(): HealthStatus
    {
        try {
            $this->db->connection()->select('select 1');

            return HealthStatus::up();
        } catch (Throwable $e) {
            // Детали только в лог: readiness-эндпоинт не должен раскрывать инфраструктуру
            $this->logger->error(
                message: 'Health check failed: database',
                context: ['exception' => $e]
            );

            return HealthStatus::down();
        }
    }
}
