<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use LaravelBoilerplate\Identity\Domain\Token\RefreshToken;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenRepository;
use LaravelBoilerplate\Identity\Domain\Token\TokenFamilyId;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Row;
use LaravelBoilerplate\Shared\Infrastructure\Persistence\Timestamp;
use stdClass;

final readonly class DatabaseRefreshTokenRepository implements RefreshTokenRepository
{
    public function __construct(
        private ConnectionResolverInterface $db,
        private RefreshTokenMapper $mapper,
    ) {}

    public function lockByHash(string $hash): ?RefreshToken
    {
        $row = $this->table()->where('token_hash', $hash)->lockForUpdate()->first();

        return $row instanceof stdClass
            ? $this->mapper->toDomain(Row::from($row, RefreshTokensTable::NAME))
            : null;
    }

    public function save(RefreshToken $token): void
    {
        $this->table()->updateOrInsert(['id' => $token->id()->toString()], $this->mapper->toRow($token));
    }

    public function revokeFamily(TokenFamilyId $familyId, DateTimeImmutable $now): void
    {
        // Массовый апдейт вместо загрузки агрегатов: семья может быть длинной,
        // а операция сводится к одному полю
        $this->table()
            ->where('family_id', $familyId->toString())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Timestamp::format($now)]);
    }

    public function revokeAllForUser(UserId $userId, DateTimeImmutable $now): void
    {
        $this->table()
            ->where('user_id', $userId->toString())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Timestamp::format($now)]);
    }

    public function deleteExpiredBefore(DateTimeImmutable $cutoff): int
    {
        return $this->table()->where('expires_at', '<', Timestamp::format($cutoff))->delete();
    }

    private function table(): Builder
    {
        return $this->db->connection()->table(RefreshTokensTable::NAME);
    }
}
