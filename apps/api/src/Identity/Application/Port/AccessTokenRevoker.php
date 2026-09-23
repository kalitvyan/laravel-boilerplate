<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Port;

use LaravelBoilerplate\Identity\Domain\User\UserId;

interface AccessTokenRevoker
{
    public function revokeAllForUser(UserId $userId): void;

    public function revokeById(string $tokenId): void;
}
