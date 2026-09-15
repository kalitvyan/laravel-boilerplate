<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Bus;

use Illuminate\Database\ConnectionResolverInterface;
use RuntimeException;

final class RecordTransactionLevelHandler
{
    public static ?int $level = null;

    public function __construct(private readonly ConnectionResolverInterface $db) {}

    public function __invoke(RecordTransactionLevel $command): void
    {
        self::$level = $this->db->connection()->transactionLevel();

        if ($command->fail) {
            throw new RuntimeException('boom');
        }
    }
}
