<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\ReadModel;

use DateTimeImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use LaravelBoilerplate\Identity\Application\ReadModel\UserReadModel;
use LaravelBoilerplate\Identity\Application\ReadModel\UserView;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\UsersTable;
use LogicException;
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

        return $row instanceof stdClass ? $this->map($row) : null;
    }

    private function map(stdClass $row): UserView
    {
        $id = $row->id ?? null;
        $email = $row->email ?? null;
        $status = $row->status ?? null;
        $registeredAt = $row->registered_at ?? null;

        if (! is_string($id) || ! is_string($email) || ! is_string($status) || ! is_string($registeredAt)) {
            throw new LogicException('Unexpected users row shape');
        }

        return new UserView($id, $email, $status, new DateTimeImmutable($registeredAt));
    }
}
