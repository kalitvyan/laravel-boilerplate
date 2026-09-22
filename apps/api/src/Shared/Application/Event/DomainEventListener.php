<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

/**
 * Синхронная реакция внутри контекста. Выполняется в транзакции команды:
 * падение листенера откатывает всю команду.
 */
interface DomainEventListener
{
    public function __invoke(DomainEvent $event): void;
}
