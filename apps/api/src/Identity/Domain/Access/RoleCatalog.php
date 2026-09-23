<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\Access;

/**
 * Определения ролей. Реализация читает конфиг: набор прав роли — часть кода, а не данных.
 */
interface RoleCatalog
{
    public function has(Role $role): bool;

    /**
     * @return list<string>
     */
    public function permissionsOf(Role $role): array;

    /**
     * @param  list<Role>  $roles
     * @return list<string> объединение прав всех ролей, без дублей
     */
    public function permissionsOfAll(array $roles): array;
}
