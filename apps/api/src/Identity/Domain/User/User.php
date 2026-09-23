<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User;

use DateTimeImmutable;
use LaravelBoilerplate\Identity\Domain\User\Event\PasswordChanged;
use LaravelBoilerplate\Identity\Domain\User\Event\UserBlocked;
use LaravelBoilerplate\Identity\Domain\User\Event\UserRegistered;
use LaravelBoilerplate\Identity\Domain\User\Event\UserUnblocked;
use LaravelBoilerplate\Shared\Domain\Aggregate\AggregateRoot;

final class User extends AggregateRoot
{
    private function __construct(
        private readonly UserId $id,
        private readonly Email $email,
        private HashedPassword $password,
        private UserStatus $status,
        private readonly DateTimeImmutable $registeredAt,
    ) {}

    public static function register(UserId $id, Email $email, HashedPassword $password, DateTimeImmutable $now): self
    {
        $user = new self($id, $email, $password, UserStatus::Active, $now);
        $user->recordThat(new UserRegistered($id, $email, $now));

        return $user;
    }

    /**
     * Восстановление из хранилища: без событий, это не новый факт в жизни агрегата.
     */
    public static function restore(
        UserId $id,
        Email $email,
        HashedPassword $password,
        UserStatus $status,
        DateTimeImmutable $registeredAt,
    ): self {
        return new self($id, $email, $password, $status, $registeredAt);
    }

    public function block(DateTimeImmutable $now): void
    {
        if ($this->status === UserStatus::Blocked) {
            return;
        }

        $this->status = UserStatus::Blocked;
        $this->recordThat(new UserBlocked($this->id, $now));
    }

    public function unblock(DateTimeImmutable $now): void
    {
        if ($this->status === UserStatus::Active) {
            return;
        }

        $this->status = UserStatus::Active;
        $this->recordThat(new UserUnblocked($this->id, $now));
    }

    public function changePassword(HashedPassword $password, DateTimeImmutable $now): void
    {
        $this->password = $password;
        $this->recordThat(new PasswordChanged($this->id, $now));
    }

    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Blocked;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
