<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Access;

use LaravelBoilerplate\Identity\Application\Port\PrincipalFactory;
use LaravelBoilerplate\Identity\Domain\Access\Role;
use LaravelBoilerplate\Identity\Domain\Access\RoleCatalog;
use LaravelBoilerplate\Identity\Domain\Access\UserRoleRepository;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Shared\Domain\Access\Principal;

final readonly class DefaultPrincipalFactory implements PrincipalFactory
{
    public function __construct(
        private UserRoleRepository $roles,
        private RoleCatalog $catalog,
    ) {}

    public function forUser(UserId $userId, array $tokenAbilities = ['*'], ?string $tokenId = null): Principal
    {
        $roles = $this->roles->rolesOf($userId);
        $permissions = $this->catalog->permissionsOfAll($roles);

        // Токен может только сузить права пользователя, но не расширить их
        if (! in_array('*', $tokenAbilities, true)) {
            $permissions = array_values(array_intersect($permissions, $tokenAbilities));
        }

        return Principal::user(
            $userId->toString(),
            array_map(static fn (Role $role): string => $role->toString(), $roles),
            $permissions,
            $tokenId,
        );
    }
}
