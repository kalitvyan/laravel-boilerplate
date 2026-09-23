<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Port;

use DateInterval;
use LaravelBoilerplate\Identity\Application\Authentication\IssuedAccessToken;
use LaravelBoilerplate\Identity\Domain\User\UserId;

interface AccessTokenIssuer
{
    /**
     * @param  list<string>  $abilities
     */
    public function issue(UserId $userId, array $abilities, DateInterval $ttl): IssuedAccessToken;
}
