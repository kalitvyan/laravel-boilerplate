<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\Access;

use DateTimeImmutable;
use LaravelBoilerplate\Identity\Domain\User\UserId;

interface UserRoleRepository
{
    /**
     * @return list<Role>
     */
    public function rolesOf(UserId $userId): array;

    public function assign(UserId $userId, Role $role, DateTimeImmutable $now): void;

    public function revoke(UserId $userId, Role $role): void;
}
