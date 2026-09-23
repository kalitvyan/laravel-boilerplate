<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\Token;

use DateTimeImmutable;
use LaravelBoilerplate\Identity\Domain\User\UserId;

interface RefreshTokenRepository
{
    /**
     * Блокирует строку (FOR UPDATE): две параллельные ротации одного токена
     * не должны обе пройти проверку usable.
     */
    public function lockByHash(string $hash): ?RefreshToken;

    public function save(RefreshToken $token): void;

    public function revokeFamily(TokenFamilyId $familyId, DateTimeImmutable $now): void;

    public function revokeAllForUser(UserId $userId, DateTimeImmutable $now): void;

    public function deleteExpiredBefore(DateTimeImmutable $cutoff): int;
}
