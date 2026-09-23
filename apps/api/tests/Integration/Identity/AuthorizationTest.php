<?php

declare(strict_types=1);

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Identity\Application\Access\AssignRole;
use LaravelBoilerplate\Identity\Application\Access\BlockUser;
use LaravelBoilerplate\Identity\Application\Authentication\LogIn;
use LaravelBoilerplate\Identity\Application\Authentication\LogInService;
use LaravelBoilerplate\Identity\Application\Port\PrincipalFactory;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUser;
use LaravelBoilerplate\Identity\Domain\Access\UnknownRole;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\PersonalAccessToken;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Application\Exception\AccessDenied;
use LaravelBoilerplate\Shared\Domain\Access\Principal;

$register = static function (Container $app, string $email): string {
    $id = UserId::generate()->toString();
    $app->make(CommandBus::class)->dispatch(new RegisterUser($id, $email, 'correct horse battery staple'));

    return $id;
};

beforeEach(function (): void {
    $this->bus = $this->app->make(CommandBus::class);
    $this->principals = $this->app->make(PrincipalFactory::class);
});

it('assigns the default role on registration', function () use ($register): void {
    $id = $register($this->app, 'jane@example.com');

    $principal = $this->principals->forUser(UserId::fromString($id));

    expect($principal->roles)->toBe(['user'])
        ->and($principal->permissions)->toBe([]);
});

it('denies commands without the required permission', function () use ($register): void {
    $actorId = $register($this->app, 'jane@example.com');
    $targetId = $register($this->app, 'john@example.com');

    $actor = $this->principals->forUser(UserId::fromString($actorId));

    expect(fn () => $this->bus->dispatch(new BlockUser($targetId, $actor)))
        ->toThrow(AccessDenied::class, 'identity.users.block');
});

it('allows commands after the role is assigned', function () use ($register): void {
    $adminId = $register($this->app, 'admin@example.com');
    $targetId = $register($this->app, 'john@example.com');

    $this->bus->dispatch(new AssignRole($adminId, 'admin', Principal::system()));

    $admin = $this->principals->forUser(UserId::fromString($adminId));
    $this->bus->dispatch(new BlockUser($targetId, $admin));

    expect($this->app->make(UserRepository::class)->find(UserId::fromString($targetId))?->isBlocked())->toBeTrue();
});

it('revokes sessions when a user is blocked', function () use ($register): void {
    $adminId = $register($this->app, 'admin@example.com');
    $targetId = $register($this->app, 'john@example.com');
    $this->bus->dispatch(new AssignRole($adminId, 'admin', Principal::system()));

    $tokens = $this->app->make(LogInService::class)(
        new LogIn('john@example.com', 'correct horse battery staple'),
    );

    $this->bus->dispatch(new BlockUser($targetId, $this->principals->forUser(UserId::fromString($adminId))));

    expect(PersonalAccessToken::findToken($tokens->accessToken))->toBeNull()
        ->and(DB::table('identity.refresh_tokens')->whereNull('revoked_at')->count())->toBe(0);
});

it('refuses self-blocking even with the permission', function () use ($register): void {
    $adminId = $register($this->app, 'admin@example.com');
    $this->bus->dispatch(new AssignRole($adminId, 'admin', Principal::system()));

    $admin = $this->principals->forUser(UserId::fromString($adminId));

    expect(fn () => $this->bus->dispatch(new BlockUser($adminId, $admin)))
        ->toThrow(AccessDenied::class, 'yourself');
});

it('narrows permissions to token abilities', function () use ($register): void {
    $adminId = $register($this->app, 'admin@example.com');
    $this->bus->dispatch(new AssignRole($adminId, 'admin', Principal::system()));

    $full = $this->principals->forUser(UserId::fromString($adminId));
    $narrow = $this->principals->forUser(UserId::fromString($adminId), ['identity.users.read']);

    expect($full->can('identity.users.block'))->toBeTrue()
        ->and($narrow->can('identity.users.block'))->toBeFalse()
        ->and($narrow->can('identity.users.read'))->toBeTrue();
});

it('rejects unknown roles', function () use ($register): void {
    $this->bus->dispatch(new AssignRole($register($this->app, 'jane@example.com'), 'wizard', Principal::system()));
})->throws(UnknownRole::class);

it('assigns roles idempotently', function () use ($register): void {
    $id = $register($this->app, 'jane@example.com');

    $this->bus->dispatch(new AssignRole($id, 'support', Principal::system()));
    $this->bus->dispatch(new AssignRole($id, 'support', Principal::system()));

    expect($this->principals->forUser(UserId::fromString($id))->roles)->toBe(['support', 'user']);
});
