<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Access;

use LaravelBoilerplate\Identity\Domain\Access\Role;
use LaravelBoilerplate\Identity\Domain\Access\RoleCatalog;
use LogicException;

final readonly class ConfigRoleCatalog implements RoleCatalog
{
    /** @var array<string, list<string>> */
    private array $roles;

    /**
     * @param  list<string>  $knownPermissions
     * @param  array<string, list<string>>  $roles
     */
    public function __construct(array $knownPermissions, array $roles)
    {
        foreach ($roles as $name => $permissions) {
            foreach ($permissions as $permission) {
                // Опечатка в конфиге роли не должна превращаться в тихо неработающее право
                if (! in_array($permission, $knownPermissions, true)) {
                    throw new LogicException(sprintf('Role "%s" references unknown permission "%s"', $name, $permission));
                }
            }
        }

        $this->roles = $roles;
    }

    public function has(Role $role): bool
    {
        return array_key_exists($role->toString(), $this->roles);
    }

    public function permissionsOf(Role $role): array
    {
        return $this->roles[$role->toString()] ?? [];
    }

    public function permissionsOfAll(array $roles): array
    {
        $permissions = [];

        foreach ($roles as $role) {
            foreach ($this->permissionsOf($role) as $permission) {
                $permissions[$permission] = true;
            }
        }

        return array_keys($permissions);
    }
}
