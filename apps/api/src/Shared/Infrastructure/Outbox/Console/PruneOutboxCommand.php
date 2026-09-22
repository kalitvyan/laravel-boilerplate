<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\InboxTable;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxTable;
use Psr\Clock\ClockInterface;

#[Description('Delete old published outbox and processed inbox messages')]
#[Signature('outbox:prune
        {--days=7 : Keep published outbox and processed inbox messages for N days}')]
final class PruneOutboxCommand extends Command
{
    private const int CHUNK = 5000;

    public function handle(ConnectionResolverInterface $db, ClockInterface $clock): int
    {
        $days = $this->option('days');

        if (! is_string($days) || ! ctype_digit($days) || (int) $days < 1) {
            throw new InvalidArgumentException('Option --days must be a positive integer');
        }

        // Срок хранения inbox обязан превышать окно повторной доставки (ретраи + ручной retry failed jobs)
        $cutoff = $clock->now()->modify(sprintf('-%d days', (int) $days))->format('Y-m-d H:i:s.uP');
        $connection = $db->connection();

        $outbox = $this->deleteInChunks($connection->table(OutboxTable::NAME)->where('published_at', '<', $cutoff));
        $inbox = $this->deleteInChunks($connection->table(InboxTable::NAME)->where('processed_at', '<', $cutoff));

        $this->info(sprintf('Pruned %d outbox and %d inbox messages', $outbox, $inbox));

        return self::SUCCESS;
    }

    private function deleteInChunks(Builder $query): int
    {
        $total = 0;

        do {
            // Короткие транзакции вместо одного гигантского DELETE
            $deleted = (clone $query)->limit(self::CHUNK)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
