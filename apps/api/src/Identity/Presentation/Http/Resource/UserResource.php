<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Presentation\Http\Resource;

use LaravelBoilerplate\Identity\Application\ReadModel\UserView;
use LaravelBoilerplate\Shared\Domain\Access\Principal;

final readonly class UserResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(UserView $user, Principal $principal): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'status' => $user->status,
            'registeredAt' => $user->registeredAt->format(DATE_RFC3339_EXTENDED),
            'roles' => $principal->roles,
            'permissions' => $principal->permissions,
        ];
    }
}
