<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Persistence;

use Illuminate\Database\UniqueConstraintViolationException;
use LaravelBoilerplate\Identity\Domain\User\Email;
use LaravelBoilerplate\Identity\Domain\User\EmailAlreadyRegistered;
use LaravelBoilerplate\Identity\Domain\User\User;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use LaravelBoilerplate\Shared\Application\Event\EventCollector;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(
        private UserMapper $mapper,
        private EventCollector $events,
    ) {}

    public function find(UserId $id): ?User
    {
        $model = UserModel::query()->find($id->toString());

        return $model instanceof UserModel ? $this->mapper->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::query()->where('email', $email->toString())->first();

        return $model instanceof UserModel ? $this->mapper->toDomain($model) : null;
    }

    public function save(User $user): void
    {
        $model = UserModel::query()->find($user->id()->toString());

        if (! $model instanceof UserModel) {
            $model = new UserModel;
        }

        $this->mapper->fill($model, $user);

        try {
            $model->save();
        } catch (UniqueConstraintViolationException) {
            // В Postgres после нарушения ограничения транзакция помечается как abort:
            // исключение обязано откатить её, что и делает шина команд
            throw EmailAlreadyRegistered::create();
        }

        $this->events->collect(...$user->releaseEvents());
    }
}
