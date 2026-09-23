<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Access;

use LaravelBoilerplate\Shared\Application\Bus\ActorAware;
use LaravelBoilerplate\Shared\Application\Bus\Command;
use LaravelBoilerplate\Shared\Application\Bus\RequiresPermission;
use LaravelBoilerplate\Shared\Domain\Access\Principal;

#[RequiresPermission('identity.users.block')]
final readonly class BlockUser implements ActorAware, Command
{
    public function __construct(
        public string $userId,
        private Principal $actor,
    ) {}

    public function actor(): Principal
    {
        return $this->actor;
    }
}
