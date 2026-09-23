<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\Token;

use DateTimeImmutable;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Shared\Domain\Aggregate\AggregateRoot;

final class RefreshToken extends AggregateRoot
{
    private function __construct(
        private readonly RefreshTokenId $id,
        private readonly TokenFamilyId $familyId,
        private readonly UserId $userId,
        private readonly string $hash,
        private readonly DateTimeImmutable $expiresAt,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $usedAt,
        private ?DateTimeImmutable $revokedAt,
    ) {}

    public static function issue(
        RefreshTokenId $id,
        TokenFamilyId $familyId,
        UserId $userId,
        string $hash,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $now,
    ): self {
        return new self($id, $familyId, $userId, $hash, $expiresAt, $now, null, null);
    }

    public static function restore(
        RefreshTokenId $id,
        TokenFamilyId $familyId,
        UserId $userId,
        string $hash,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $usedAt,
        ?DateTimeImmutable $revokedAt,
    ): self {
        return new self($id, $familyId, $userId, $hash, $expiresAt, $createdAt, $usedAt, $revokedAt);
    }

    public function isUsable(DateTimeImmutable $now): bool
    {
        return ! $this->usedAt instanceof DateTimeImmutable && ! $this->revokedAt instanceof DateTimeImmutable && $this->expiresAt > $now;
    }

    /**
     * Использованный, но не отозванный токен — признак утечки: легитимный клиент
     * после ротации предъявляет уже новый токен.
     */
    public function wasUsed(): bool
    {
        return $this->usedAt instanceof DateTimeImmutable;
    }

    public function markUsed(DateTimeImmutable $now): void
    {
        $this->usedAt ??= $now;
    }

    public function revoke(DateTimeImmutable $now): void
    {
        $this->revokedAt ??= $now;
    }

    public function id(): RefreshTokenId
    {
        return $this->id;
    }

    public function familyId(): TokenFamilyId
    {
        return $this->familyId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function usedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }
}
