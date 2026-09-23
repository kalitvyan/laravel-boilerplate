<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User;

interface UserRepository
{
    public function find(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /**
     * Сохраняет состояние и передаёт освобождённые события коллектору.
     *
     * @throws EmailAlreadyRegistered при гонке двух регистраций с одним email
     */
    public function save(User $user): void;
}
