<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\GetUser;

use LaravelBoilerplate\Identity\Application\ReadModel\UserReadModel;
use LaravelBoilerplate\Identity\Application\ReadModel\UserView;
use LaravelBoilerplate\Identity\Application\UserNotFound;
use LaravelBoilerplate\Identity\Domain\User\UserId;

final readonly class GetUserHandler
{
    public function __construct(private UserReadModel $users) {}

    public function __invoke(GetUser $query): UserView
    {
        // Валидация формата до запроса в БД: Postgres отверг бы не-UUID ошибкой приведения типа
        $userId = UserId::fromString($query->userId)->toString();

        return $this->users->find($userId) ?? throw UserNotFound::withId($userId);
    }
}
