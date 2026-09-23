<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenRepository;
use Psr\Clock\ClockInterface;

#[Description('Delete refresh tokens that expired long ago')]
#[Signature('identity:prune-refresh-tokens {--days=7 : Keep expired tokens for N days}')]
final class PruneRefreshTokensCommand extends Command
{
    public function handle(RefreshTokenRepository $tokens, ClockInterface $clock): int
    {
        $days = $this->option('days');

        if (! is_string($days) || ! ctype_digit($days) || (int) $days < 1) {
            throw new InvalidArgumentException('Option --days must be a positive integer');
        }

        // Отозванные, но ещё не истёкшие токены сохраняем: они нужны для детекции reuse
        $deleted = $tokens->deleteExpiredBefore($clock->now()->modify(sprintf('-%d days', (int) $days)));

        $this->info(sprintf('Pruned %d refresh tokens', $deleted));

        return self::SUCCESS;
    }
}
