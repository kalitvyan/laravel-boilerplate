<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Sanctum;

use LaravelBoilerplate\Identity\Application\Port\AccessTokenRevoker;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\PersonalAccessToken;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\UserModel;

final readonly class SanctumAccessTokenRevoker implements AccessTokenRevoker
{
    public function revokeAllForUser(UserId $userId): void
    {
        PersonalAccessToken::query()
            ->where('tokenable_type', new UserModel()->getMorphClass())
            ->where('tokenable_id', $userId->toString())
            ->delete();
    }

    public function revokeById(string $tokenId): void
    {
        PersonalAccessToken::query()->whereKey($tokenId)->delete();
    }
}
