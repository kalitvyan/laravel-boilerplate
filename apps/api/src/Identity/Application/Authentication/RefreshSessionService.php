<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use LaravelBoilerplate\Identity\Application\Port\AccessTokenRevoker;
use LaravelBoilerplate\Identity\Application\Port\SecretHasher;
use LaravelBoilerplate\Identity\Domain\Token\RefreshToken;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenRepository;
use LaravelBoilerplate\Identity\Domain\User\User;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

final readonly class RefreshSessionService
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private UserRepository $users,
        private AccessTokenRevoker $accessTokens,
        private SessionIssuer $sessions,
        private SecretHasher $hasher,
        private TransactionManager $transactions,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(string $plainRefreshToken): SessionTokens
    {
        $hash = $this->hasher->hash($plainRefreshToken);

        $rotation = $this->transactions->transactional(function () use ($hash): RotationOutcome {
            $now = $this->clock->now();
            $token = $this->refreshTokens->lockByHash($hash);

            if (! $token instanceof RefreshToken) {
                return RotationOutcome::invalid();
            }

            if ($token->wasUsed()) {
                // Токен уже ротирован: либо утечка, либо параллельный клиент.
                // Отзыв обязан пережить последующую ошибку, поэтому только сигнализируем
                return RotationOutcome::compromised($token->familyId(), $token->userId());
            }

            if (! $token->isUsable($now)) {
                return RotationOutcome::invalid();
            }

            $user = $this->users->find($token->userId());

            if (! $user instanceof User || $user->isBlocked()) {
                return RotationOutcome::compromised($token->familyId(), $token->userId(), revokeAccessTokens: false);
            }

            $token->markUsed($now);
            $this->refreshTokens->save($token);

            return RotationOutcome::rotated($this->sessions->issue($user->id(), $token->familyId()));
        });

        if ($rotation->tokens !== null) {
            return $rotation->tokens;
        }

        if ($rotation->compromisedFamily !== null && $rotation->compromisedUser !== null) {
            $this->logger->warning('Refresh token rejected, revoking the whole family', [
                'user_id' => $rotation->compromisedUser->toString(),
                'family_id' => $rotation->compromisedFamily->toString(),
            ]);

            // Отдельная транзакция: она коммитится, даже когда клиент получит 401
            $this->transactions->transactional(function () use ($rotation): void {
                $this->refreshTokens->revokeFamily($rotation->compromisedFamily, $this->clock->now());

                if ($rotation->revokeAccessTokens) {
                    $this->accessTokens->revokeAllForUser($rotation->compromisedUser);
                }
            });
        }

        throw InvalidRefreshToken::create();
    }
}
