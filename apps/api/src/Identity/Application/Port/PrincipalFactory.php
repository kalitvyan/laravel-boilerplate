<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Port;

use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Shared\Domain\Access\Principal;

interface PrincipalFactory
{
    /**
     * @param  list<string>  $tokenAbilities  abilities выданного токена; ['*'] — без сужения
     */
    public function forUser(UserId $userId, array $tokenAbilities = ['*'], ?string $tokenId = null): Principal;
}
