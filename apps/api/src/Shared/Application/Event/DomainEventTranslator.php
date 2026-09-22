<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Event;

use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

/**
 * Превращает доменное событие в интеграционное. Регистрируется только для тех событий,
 * которые контекст осознанно публикует наружу.
 */
interface DomainEventTranslator
{
    public function __invoke(DomainEvent $event): IntegrationEvent;
}
