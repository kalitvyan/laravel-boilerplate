<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus;

interface CommandMiddleware
{
    /**
     * @param  callable(Command): void  $next
     */
    public function handle(Command $command, callable $next): void;
}
