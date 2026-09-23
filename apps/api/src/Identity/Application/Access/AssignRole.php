<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Access;

use LaravelBoilerplate\Shared\Application\Bus\ActorAware;
use LaravelBoilerplate\Shared\Application\Bus\Command;
use LaravelBoilerplate\Shared\Application\Bus\RequiresPermission;
use LaravelBoilerplate\Shared\Domain\Access\Principal;

#[RequiresPermission('identity.roles.assign')]
final readonly class AssignRole implements ActorAware, Command
{
    public function __construct(
        public string $userId,
        public string $role,
        private Principal $actor,
    ) {}

    public function actor(): Principal
    {
        return $this->actor;
    }
}
