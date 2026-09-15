<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use LaravelBoilerplate\Shared\Application\Bus\Query;
use LaravelBoilerplate\Shared\Application\Bus\QueryBus;
use LogicException;

final readonly class ContainerQueryBus implements QueryBus
{
    public function __construct(
        private Container $container,
        private QueryHandlerMap $handlers,
    ) {}

    public function ask(Query $query): mixed
    {
        $handlerClass = $this->handlers->handlerFor($query);
        $handler = $this->container->make($handlerClass);

        if (! is_callable($handler)) {
            throw new LogicException(sprintf('Handler %s must be invokable', $handlerClass));
        }

        return $handler($query);
    }
}
