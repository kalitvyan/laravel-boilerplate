<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Event;

use LaravelBoilerplate\Shared\Application\Event\IntegrationEventEnvelope;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventHandler;
use Psr\Log\LoggerInterface;

/**
 * Первый подписчик появится в следующем контексте; пока этот замыкает путь
 * outbox → queue → inbox и делает его наблюдаемым.
 */
final readonly class LogUserRegistered implements IntegrationEventHandler
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(IntegrationEventEnvelope $envelope): void
    {
        $this->logger->info('User registered', [
            'user_id' => $envelope->aggregateId,
            'message_id' => $envelope->messageId,
        ]);
    }
}
