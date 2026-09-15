<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Bus;

use LogicException;

/**
 * Иммутабельная карта message => handler.
 * Заполняется при бутстрапе, поэтому безопасна для Octane.
 */
abstract readonly class HandlerMap
{
    /**
     * @param  array<class-string, class-string>  $handlers
     */
    final public function __construct(private array $handlers = []) {}

    /**
     * @param  array<class-string, class-string>  $handlers
     */
    public function with(array $handlers): static
    {
        foreach (array_keys($handlers) as $message) {
            if (isset($this->handlers[$message])) {
                throw new LogicException(
                    message: sprintf('Handler for %s is already registered', $message)
                );
            }
        }

        return new static([...$this->handlers, ...$handlers]);
    }

    /**
     * @return class-string
     */
    public function handlerFor(object $message): string
    {
        return $this->handlers[$message::class]
            ?? throw new LogicException(
                message: sprintf('No handler registered for %s', $message::class)
            );
    }

    /**
     * @return array<class-string, class-string>
     */
    public function all(): array
    {
        return $this->handlers;
    }
}
