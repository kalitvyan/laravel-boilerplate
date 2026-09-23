<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Access;

use LaravelBoilerplate\Identity\Application\UserNotFound;
use LaravelBoilerplate\Identity\Domain\Access\Role;
use LaravelBoilerplate\Identity\Domain\Access\RoleCatalog;
use LaravelBoilerplate\Identity\Domain\Access\UnknownRole;
use LaravelBoilerplate\Identity\Domain\Access\UserRoleRepository;
use LaravelBoilerplate\Identity\Domain\User\User;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use Psr\Clock\ClockInterface;

final readonly class AssignRoleHandler
{
    public function __construct(
        private UserRepository $users,
        private UserRoleRepository $roles,
        private RoleCatalog $catalog,
        private ClockInterface $clock,
    ) {}

    public function __invoke(AssignRole $command): void
    {
        $userId = UserId::fromString($command->userId);
        $role = Role::fromString($command->role);

        if (! $this->catalog->has($role)) {
            throw UnknownRole::named($command->role);
        }

        if (! $this->users->find($userId) instanceof User) {
            throw UserNotFound::withId($command->userId);
        }

        $this->roles->assign($userId, $role, $this->clock->now());
    }
}
