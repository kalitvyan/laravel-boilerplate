<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\RegisterUser;

use LaravelBoilerplate\Identity\Application\Port\PasswordHasher;
use LaravelBoilerplate\Identity\Domain\User\Email;
use LaravelBoilerplate\Identity\Domain\User\EmailAlreadyRegistered;
use LaravelBoilerplate\Identity\Domain\User\PlainPassword;
use LaravelBoilerplate\Identity\Domain\User\User;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use Psr\Clock\ClockInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private ClockInterface $clock,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        $email = Email::fromString($command->email);

        // Быстрый путь для понятной ошибки; гонки ловит уникальный индекс в репозитории
        if ($this->users->findByEmail($email) instanceof User) {
            throw EmailAlreadyRegistered::create();
        }

        $user = User::register(
            UserId::fromString($command->userId),
            $email,
            $this->hasher->hash(PlainPassword::fromString($command->password)),
            $this->clock->now(),
        );

        $this->users->save($user);
    }
}
