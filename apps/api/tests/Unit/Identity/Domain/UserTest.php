<?php

declare(strict_types=1);

use LaravelBoilerplate\Identity\Domain\User\Email;
use LaravelBoilerplate\Identity\Domain\User\Event\UserBlocked;
use LaravelBoilerplate\Identity\Domain\User\Event\UserRegistered;
use LaravelBoilerplate\Identity\Domain\User\HashedPassword;
use LaravelBoilerplate\Identity\Domain\User\User;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserStatus;

beforeEach(function (): void {
    $this->now = new DateTimeImmutable('2026-01-01 10:00:00');
    $this->user = User::register(
        UserId::generate(),
        Email::fromString('jane@example.com'),
        HashedPassword::fromHash('hash'),
        $this->now,
    );
});

it('records registration', function (): void {
    $events = $this->user->releaseEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserRegistered::class)
        ->and($this->user->status())->toBe(UserStatus::Active);
});

it('blocks idempotently', function (): void {
    $this->user->releaseEvents();

    $this->user->block($this->now);
    $this->user->block($this->now);

    $events = $this->user->releaseEvents();

    expect($this->user->isBlocked())->toBeTrue()
        ->and($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserBlocked::class);
});

it('restores without recording events', function (): void {
    $restored = User::restore(
        UserId::generate(),
        Email::fromString('jane@example.com'),
        HashedPassword::fromHash('hash'),
        UserStatus::Blocked,
        $this->now,
    );

    expect($restored->releaseEvents())->toBe([])
        ->and($restored->isBlocked())->toBeTrue();
});
