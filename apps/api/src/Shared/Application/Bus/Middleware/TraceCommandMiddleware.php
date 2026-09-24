<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus\Middleware;

use LaravelBoilerplate\Shared\Application\Bus\Command;
use LaravelBoilerplate\Shared\Application\Bus\CommandMiddleware;
use LaravelBoilerplate\Shared\Application\Tracing\Tracer;

final readonly class TraceCommandMiddleware implements CommandMiddleware
{
    public function __construct(private Tracer $tracer) {}

    public function handle(Command $command, callable $next): void
    {
        $this->tracer->span(
            self::shortName($command::class),
            ['messaging.message.type' => $command::class],
            static function () use ($command, $next): void {
                $next($command);
            },
        );
    }

    private function shortName(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
