<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

/**
 * Консьюмер интеграционного события. Выполняется в транзакции вместе с записью в inbox:
 * изменения в БД применяются ровно один раз. Внешние побочные эффекты (email, HTTP)
 * остаются at-least-once и должны быть идемпотентны сами по себе.
 */
interface IntegrationEventHandler
{
    public function __invoke(IntegrationEventEnvelope $envelope): void;
}
