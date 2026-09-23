<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use LaravelBoilerplate\Identity\Domain\User\Email;
use LaravelBoilerplate\Identity\Domain\User\HashedPassword;
use LaravelBoilerplate\Identity\Domain\User\User;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserStatus;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\UserModel;

final readonly class UserMapper
{
    public function toDomain(UserModel $model): User
    {
        return User::restore(
            UserId::fromString($model->id),
            Email::fromString($model->email),
            HashedPassword::fromHash($model->password_hash),
            UserStatus::from($model->status),
            $model->registered_at->toDateTimeImmutable(),
        );
    }

    public function fill(UserModel $model, User $user): void
    {
        $model->id = $user->id()->toString();
        $model->email = $user->email()->toString();
        $model->password_hash = $user->password()->toString();
        $model->status = $user->status()->value;
        $model->registered_at = CarbonImmutable::instance($user->registeredAt());
    }
}
