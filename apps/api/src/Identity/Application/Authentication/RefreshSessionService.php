<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use DateInterval;
use DateTimeImmutable;
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
        private DateInterval $grace,
    ) {}

    public function __invoke(string $plainRefreshToken): SessionTokens
    {
        $hash = $this->hasher->hash($plainRefreshToken);

        $rotation = $this->transactions->transactional(fn (): RotationOutcome => $this->rotate($hash));

        if ($rotation->tokens !== null) {
            if ($rotation->viaGrace) {
                $this->logger->info('Refresh token reused within the grace window, treated as a race');
            }

            return $rotation->tokens;
        }

        $family = $rotation->compromisedFamily;
        $user = $rotation->compromisedUser;

        if ($family !== null && $user !== null) {
            $this->logger->warning('Refresh token rejected, revoking the whole family', [
                'user_id' => $user->toString(),
                'family_id' => $family->toString(),
            ]);

            // Отдельная транзакция: она коммитится, даже когда клиент получит 401
            $this->transactions->transactional(function () use ($family, $user, $rotation): void {
                $this->refreshTokens->revokeFamily($family, $this->clock->now());

                if ($rotation->revokeAccessTokens) {
                    $this->accessTokens->revokeAllForUser($user);
                }
            });
        }

        throw InvalidRefreshToken::create();
    }

    private function rotate(string $hash): RotationOutcome
    {
        $now = $this->clock->now();
        $token = $this->refreshTokens->lockByHash($hash);

        if (! $token instanceof RefreshToken || $token->revokedAt() instanceof DateTimeImmutable || $token->isExpired($now)) {
            return RotationOutcome::invalid();
        }

        if ($token->wasUsed()) {
            $usedAt = $token->usedAt();

            // Гонка, а не утечка: тот же токен предъявлен в пределах окна
            if ($usedAt instanceof DateTimeImmutable && $usedAt > $now->sub($this->grace)) {
                return $this->issueFor($token, markUsed: false, viaGrace: true);
            }

            return RotationOutcome::compromised($token->familyId(), $token->userId());
        }

        return $this->issueFor($token, markUsed: true, viaGrace: false);
    }

    private function issueFor(RefreshToken $token, bool $markUsed, bool $viaGrace): RotationOutcome
    {
        $user = $this->users->find($token->userId());

        if (! $user instanceof User || $user->isBlocked()) {
            return RotationOutcome::compromised($token->familyId(), $token->userId(), revokeAccessTokens: false);
        }

        if ($markUsed) {
            $token->markUsed($this->clock->now());
            $this->refreshTokens->save($token);
        }

        return RotationOutcome::rotated($this->sessions->issue($user->id(), $token->familyId()), $viaGrace);
    }
}
