<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use DateInterval;
use LaravelBoilerplate\Identity\Application\Port\AccessTokenIssuer;
use LaravelBoilerplate\Identity\Application\Port\SecretGenerator;
use LaravelBoilerplate\Identity\Application\Port\SecretHasher;
use LaravelBoilerplate\Identity\Domain\Token\RefreshToken;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenId;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenRepository;
use LaravelBoilerplate\Identity\Domain\Token\TokenFamilyId;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use Psr\Clock\ClockInterface;

final readonly class SessionIssuer
{
    public function __construct(
        private AccessTokenIssuer $accessTokens,
        private RefreshTokenRepository $refreshTokens,
        private SecretGenerator $secrets,
        private SecretHasher $hasher,
        private ClockInterface $clock,
        private DateInterval $accessTtl,
        private DateInterval $refreshTtl,
    ) {}

    /**
     * @param  list<string>  $abilities
     */
    public function issue(UserId $userId, TokenFamilyId $familyId, array $abilities = ['*']): SessionTokens
    {
        $now = $this->clock->now();

        $accessToken = $this->accessTokens->issue($userId, $abilities, $this->accessTtl);

        $plainRefreshToken = $this->secrets->generate();
        $refreshExpiresAt = $now->add($this->refreshTtl);

        $this->refreshTokens->save(RefreshToken::issue(
            RefreshTokenId::generate(),
            $familyId,
            $userId,
            $this->hasher->hash($plainRefreshToken),
            $refreshExpiresAt,
            $now,
        ));

        return new SessionTokens(
            $userId->toString(),
            $accessToken->value,
            $accessToken->expiresAt,
            $plainRefreshToken,
            $refreshExpiresAt,
        );
    }
}
