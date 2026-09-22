<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Event;

use Illuminate\Contracts\Container\Container;
use LaravelBoilerplate\Shared\Application\Event\DomainEventTranslator;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEvent;
use LaravelBoilerplate\Shared\Application\Event\IntegrationEventFactory;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;
use LogicException;

final readonly class MappedIntegrationEventFactory implements IntegrationEventFactory
{
    public function __construct(
        private Container $container,
        private DomainEventTranslatorMap $translators,
    ) {}

    public function translate(DomainEvent $event): ?IntegrationEvent
    {
        $translatorClass = $this->translators->find($event);

        if ($translatorClass === null) {
            return null;
        }

        $translator = $this->container->make($translatorClass);

        if (! $translator instanceof DomainEventTranslator) {
            throw new LogicException(sprintf('%s must implement %s', $translatorClass, DomainEventTranslator::class));
        }

        return $translator($event);
    }
}
