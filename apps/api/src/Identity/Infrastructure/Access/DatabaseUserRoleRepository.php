<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Access;

use DateTimeImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use LaravelBoilerplate\Identity\Domain\Access\Role;
use LaravelBoilerplate\Identity\Domain\Access\UserRoleRepository;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\UserRolesTable;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Timestamp;

final readonly class DatabaseUserRoleRepository implements UserRoleRepository
{
    public function __construct(private ConnectionResolverInterface $db) {}

    public function rolesOf(UserId $userId): array
    {
        $rows = $this->db->connection()
            ->table(UserRolesTable::NAME)
            ->where('user_id', $userId->toString())
            ->orderBy('role')
            ->pluck('role')
            ->all();

        return array_values(array_map(
            static fn (mixed $role): Role => Role::fromString(is_string($role) ? $role : ''),
            $rows,
        ));
    }

    public function assign(UserId $userId, Role $role, DateTimeImmutable $now): void
    {
        // Повторное назначение роли — не ошибка
        $this->db->connection()->table(UserRolesTable::NAME)->insertOrIgnore([
            'user_id' => $userId->toString(),
            'role' => $role->toString(),
            'assigned_at' => Timestamp::format($now),
        ]);
    }

    public function revoke(UserId $userId, Role $role): void
    {
        $this->db->connection()
            ->table(UserRolesTable::NAME)
            ->where('user_id', $userId->toString())
            ->where('role', $role->toString())
            ->delete();
    }
}
