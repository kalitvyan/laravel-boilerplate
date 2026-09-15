<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus;

interface CommandBus
{
    public function dispatch(Command $command): void;
}
