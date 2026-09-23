<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Persistence;

use DateTimeImmutable;
use LaravelBoilerplate\Identity\Domain\Token\RefreshToken;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenId;
use LaravelBoilerplate\Identity\Domain\Token\TokenFamilyId;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Row;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Timestamp;

final readonly class RefreshTokenMapper
{
    public function toDomain(Row $row): RefreshToken
    {
        return RefreshToken::restore(
            RefreshTokenId::fromString($row->string('id')),
            TokenFamilyId::fromString($row->string('family_id')),
            UserId::fromString($row->string('user_id')),
            $row->string('token_hash'),
            $row->timestamp('expires_at'),
            $row->timestamp('created_at'),
            $row->nullableTimestamp('used_at'),
            $row->nullableTimestamp('revoked_at'),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toRow(RefreshToken $token): array
    {
        $usedAt = $token->usedAt();
        $revokedAt = $token->revokedAt();

        return [
            'family_id' => $token->familyId()->toString(),
            'user_id' => $token->userId()->toString(),
            'token_hash' => $token->hash(),
            'expires_at' => Timestamp::format($token->expiresAt()),
            'created_at' => Timestamp::format($token->createdAt()),
            'used_at' => $usedAt instanceof DateTimeImmutable ? Timestamp::format($usedAt) : null,
            'revoked_at' => $revokedAt instanceof DateTimeImmutable ? Timestamp::format($revokedAt) : null,
        ];
    }
}
