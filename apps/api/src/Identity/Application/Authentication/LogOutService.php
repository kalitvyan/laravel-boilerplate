<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use LaravelBoilerplate\Identity\Application\Port\AccessTokenRevoker;
use LaravelBoilerplate\Identity\Application\Port\SecretHasher;
use LaravelBoilerplate\Identity\Domain\Token\RefreshToken;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenRepository;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use Psr\Clock\ClockInterface;

final readonly class LogOutService
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private AccessTokenRevoker $accessTokens,
        private SecretHasher $hasher,
        private TransactionManager $transactions,
        private ClockInterface $clock,
    ) {}

    /**
     * Идемпотентен: выход по уже недействительному токену — не ошибка.
     */
    public function __invoke(?string $plainRefreshToken, ?string $accessTokenId = null): void
    {
        $this->transactions->transactional(function () use ($plainRefreshToken, $accessTokenId): void {
            if ($plainRefreshToken !== null) {
                $token = $this->refreshTokens->lockByHash($this->hasher->hash($plainRefreshToken));

                if ($token instanceof RefreshToken) {
                    $this->refreshTokens->revokeFamily($token->familyId(), $this->clock->now());
                }
            }

            if ($accessTokenId !== null) {
                $this->accessTokens->revokeById($accessTokenId);
            }
        });
    }
}
