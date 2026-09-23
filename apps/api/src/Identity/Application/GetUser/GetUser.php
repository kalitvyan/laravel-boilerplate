<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\GetUser;

use LaravelBoilerplate\Identity\Application\ReadModel\UserView;
use LaravelBoilerplate\Shared\Application\Bus\Query;

/**
 * @implements Query<UserView>
 */
final readonly class GetUser implements Query
{
    public function __construct(public string $userId) {}
}
