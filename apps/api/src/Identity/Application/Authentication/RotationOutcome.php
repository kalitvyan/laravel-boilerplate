<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use LaravelBoilerplate\Identity\Domain\Token\TokenFamilyId;
use LaravelBoilerplate\Identity\Domain\User\UserId;

/**
 * Результат попытки ротации. Нужен, чтобы отзыв семьи выполнялся вне транзакции,
 * которую откатит InvalidRefreshToken.
 */
final readonly class RotationOutcome
{
    private function __construct(
        public ?SessionTokens $tokens,
        public ?TokenFamilyId $compromisedFamily,
        public ?UserId $compromisedUser,
        public bool $revokeAccessTokens,
        public bool $viaGrace,
    ) {}

    public static function rotated(SessionTokens $tokens, bool $viaGrace = false): self
    {
        return new self($tokens, null, null, false, $viaGrace);
    }

    public static function invalid(): self
    {
        return new self(null, null, null, false, false);
    }

    public static function compromised(
        TokenFamilyId $familyId,
        UserId $userId,
        bool $revokeAccessTokens = true,
    ): self {
        return new self(null, $familyId, $userId, $revokeAccessTokens, false);
    }
}
