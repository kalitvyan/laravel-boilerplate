<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Event;

use LaravelBoilerplate\Shared\Application\Event\IntegrationEventHandler;
use LogicException;

final readonly class IntegrationEventSubscriberMap
{
    /**
     * @param  array<string, list<class-string<IntegrationEventHandler>>>  $subscribers  ключ "name@version"
     */
    public function __construct(private array $subscribers = []) {}

    /**
     * @param  list<class-string<IntegrationEventHandler>>  $handlers
     */
    public function with(string $eventName, int $version, array $handlers): self
    {
        $key = $this->key($eventName, $version);
        $existing = $this->subscribers[$key] ?? [];

        foreach ($handlers as $handler) {
            if (in_array($handler, $existing, true)) {
                throw new LogicException(sprintf('%s is already subscribed to %s', $handler, $key));
            }
        }

        return new self([...$this->subscribers, $key => [...$existing, ...$handlers]]);
    }

    /**
     * @return list<class-string<IntegrationEventHandler>>
     */
    public function handlersFor(string $eventName, int $version): array
    {
        return $this->subscribers[$this->key($eventName, $version)] ?? [];
    }

    private function key(string $eventName, int $version): string
    {
        return $eventName.'@'.$version;
    }
}
