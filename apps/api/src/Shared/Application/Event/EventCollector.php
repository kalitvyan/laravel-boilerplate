<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

/**
 * Собирает события, освобождённые агрегатами, в пределах одного запроса или джоба.
 */
interface EventCollector
{
    public function collect(DomainEvent ...$events): void;

    /**
     * @return list<DomainEvent> возвращает собранное и очищает буфер
     */
    public function release(): array;
}
