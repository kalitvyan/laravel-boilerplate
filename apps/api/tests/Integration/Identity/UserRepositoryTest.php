<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Identity\Domain\User\Email;
use LaravelBoilerplate\Identity\Domain\User\EmailAlreadyRegistered;
use LaravelBoilerplate\Identity\Domain\User\HashedPassword;
use LaravelBoilerplate\Identity\Domain\User\User;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Identity\Domain\User\UserStatus;
use LaravelBoilerplate\Shared\Application\Event\EventCollector;

$newUser = static fn (string $email = 'jane@example.com'): User => User::register(
    UserId::generate(),
    Email::fromString($email),
    HashedPassword::fromHash('$2y$04$not-a-real-hash'),
    new DateTimeImmutable('2026-01-01 10:00:00.123456+00:00'),
);

beforeEach(function (): void {
    $this->users = $this->app->make(UserRepository::class);
});

it('round-trips an aggregate', function () use ($newUser): void {
    $user = $newUser();
    $this->users->save($user);

    $restored = $this->users->find($user->id());

    expect($restored)->not->toBeNull()
        ->and($restored->id()->equals($user->id()))->toBeTrue()
        ->and($restored->email()->equals($user->email()))->toBeTrue()
        ->and($restored->status())->toBe(UserStatus::Active)
        // Микросекунды и часовой пояс не теряются по дороге через Eloquent
        ->and($restored->registeredAt()->format('Y-m-d H:i:s.u P'))->toBe('2026-01-01 10:00:00.123456 +00:00');
});

it('finds by email', function () use ($newUser): void {
    $this->users->save($newUser('jane@example.com'));

    expect($this->users->findByEmail(Email::fromString('JANE@example.com')))->not->toBeNull()
        ->and($this->users->findByEmail(Email::fromString('john@example.com')))->toBeNull();
});

it('persists state changes', function () use ($newUser): void {
    $user = $newUser();
    $this->users->save($user);

    $user->block(new DateTimeImmutable);
    $this->users->save($user);

    expect($this->users->find($user->id())?->isBlocked())->toBeTrue();
});

it('translates a unique violation into a domain error', function () use ($newUser): void {
    $this->users->save($newUser('dup@example.com'));

    // Savepoint: нарушение ограничения иначе переведёт в abort внешнюю транзакцию теста
    expect(fn () => DB::transaction(fn () => $this->users->save($newUser('dup@example.com'))))
        ->toThrow(EmailAlreadyRegistered::class);
});

it('hands released events to the collector', function () use ($newUser): void {
    $collector = $this->app->make(EventCollector::class);
    $collector->release();

    $this->users->save($newUser());

    expect($collector->release())->toHaveCount(1);
});
