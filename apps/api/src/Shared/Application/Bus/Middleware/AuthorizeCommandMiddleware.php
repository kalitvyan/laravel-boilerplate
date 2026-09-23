<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus\Middleware;

use LaravelBoilerplate\Shared\Application\Bus\ActorAware;
use LaravelBoilerplate\Shared\Application\Bus\Command;
use LaravelBoilerplate\Shared\Application\Bus\CommandMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\RequiresPermission;
use LaravelBoilerplate\Shared\Application\Exception\AccessDenied;
use LogicException;
use ReflectionClass;

final class AuthorizeCommandMiddleware implements CommandMiddleware
{
    /** @var array<class-string, string|null> */
    private array $cache = [];

    public function handle(Command $command, callable $next): void
    {
        $permission = $this->permissionFor($command);

        if ($permission !== null) {
            if (! $command instanceof ActorAware) {
                throw new LogicException(sprintf(
                    '%s requires permission "%s" but does not implement %s',
                    $command::class,
                    $permission,
                    ActorAware::class,
                ));
            }

            if (! $command->actor()->can($permission)) {
                throw new AccessDenied(sprintf('Permission "%s" is required', $permission));
            }
        }

        $next($command);
    }

    private function permissionFor(Command $command): ?string
    {
        return $this->cache[$command::class] ??= $this->readAttribute($command);
    }

    private function readAttribute(Command $command): ?string
    {
        $attributes = new ReflectionClass($command)->getAttributes(RequiresPermission::class);

        return $attributes === [] ? null : $attributes[0]->newInstance()->permission;
    }
}
