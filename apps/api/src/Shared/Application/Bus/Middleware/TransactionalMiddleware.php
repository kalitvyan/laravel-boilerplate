<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus\Middleware;

use LaravelBoilerplate\Shared\Application\Bus\Command;
use LaravelBoilerplate\Shared\Application\Bus\CommandMiddleware;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;

final readonly class TransactionalMiddleware implements CommandMiddleware
{
    public function __construct(private TransactionManager $transactions) {}

    public function handle(Command $command, callable $next): void
    {
        $this
            ->transactions
            ->transactional(static function () use ($command, $next): void {
                $next($command);
            });
    }
}
