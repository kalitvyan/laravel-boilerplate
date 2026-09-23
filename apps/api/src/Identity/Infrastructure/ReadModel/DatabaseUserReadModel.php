<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\ReadModel;

use Illuminate\Database\ConnectionResolverInterface;
use LaravelBoilerplate\Identity\Application\ReadModel\UserReadModel;
use LaravelBoilerplate\Identity\Application\ReadModel\UserView;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\UsersTable;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Row;
use stdClass;

final readonly class DatabaseUserReadModel implements UserReadModel
{
    public function __construct(private ConnectionResolverInterface $db) {}

    public function find(string $userId): ?UserView
    {
        $row = $this->db->connection()
            ->table(UsersTable::NAME)
            ->select(['id', 'email', 'status', 'registered_at'])
            ->where('id', $userId)
            ->first();

        if (! $row instanceof stdClass) {
            return null;
        }

        $data = Row::from($row, UsersTable::NAME);

        return new UserView(
            $data->string('id'),
            $data->string('email'),
            $data->string('status'),
            $data->timestamp('registered_at'),
        );
    }
}
