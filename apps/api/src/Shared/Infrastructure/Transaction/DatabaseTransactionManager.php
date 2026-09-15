<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Transaction;

use Illuminate\Database\ConnectionResolverInterface;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;

final readonly class DatabaseTransactionManager implements TransactionManager
{
    public function __construct(private ConnectionResolverInterface $db) {}

    public function transactional(callable $callback): mixed
    {
        // attempts = 1: ретраи на serialization failure/deadlock решаются явно, а не молча
        return $this->db->connection()->transaction(static fn (): mixed => $callback());
    }
}
