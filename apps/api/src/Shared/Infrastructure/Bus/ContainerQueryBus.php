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
        $handler = $this->container->make($this->handlers->handlerFor($query));

        if (! is_callable($handler)) {
            throw new LogicException(
                message: sprintf('Handler %s must be invokable', $handler::class)
            );
        }

        return $handler($query);
    }
}
