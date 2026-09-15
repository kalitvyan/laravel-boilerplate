<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use LaravelBoilerplate\Shared\Application\Bus\Command;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Application\Bus\CommandMiddleware;
use LogicException;

final readonly class ContainerCommandBus implements CommandBus
{
    /**
     * @param  list<CommandMiddleware>  $middleware  порядок: первый в списке — внешний
     */
    public function __construct(
        private Container $container,
        private CommandHandlerMap $handlers,
        private array $middleware = [],
    ) {}

    public function dispatch(Command $command): void
    {
        $core = function (Command $command): void {
            $handler = $this->container->make($this->handlers->handlerFor($command));

            if (! is_callable($handler)) {
                throw new LogicException(
                    message: sprintf('Handler %s must be invokable', $handler::class)
                );
            }

            $handler($command);
        };

        $pipeline = array_reduce(
            array_reverse($this->middleware),
            static fn (callable $next, CommandMiddleware $middleware): callable => static function (Command $command) use ($middleware, $next): void {
                $middleware->handle($command, $next);
            },
            $core,
        );

        $pipeline($command);
    }
}
