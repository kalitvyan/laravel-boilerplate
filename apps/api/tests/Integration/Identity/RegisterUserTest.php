<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LaravelBoilerplate\Identity\Application\GetUser\GetUser;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUser;
use LaravelBoilerplate\Identity\Application\UserNotFound;
use LaravelBoilerplate\Identity\Domain\User\EmailAlreadyRegistered;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\WeakPassword;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Application\Bus\QueryBus;
use LaravelBoilerplate\Shared\Domain\Exception\InvalidIdentifier;

beforeEach(function (): void {
    $this->commands = $this->app->make(CommandBus::class);
    $this->queries = $this->app->make(QueryBus::class);
});

it('registers a user with a hashed password', function (): void {
    $id = UserId::generate()->toString();

    $this->commands->dispatch(new RegisterUser($id, 'Jane@Example.com', 'correct horse battery staple'));

    $view = $this->queries->ask(new GetUser($id));
    $hash = DB::table('identity.users')->where('id', $id)->value('password_hash');

    expect($view->email)->toBe('jane@example.com')
        ->and($view->status)->toBe('active')
        ->and($hash)->not->toBe('correct horse battery staple')
        ->and(Hash::check('correct horse battery staple', (string) $hash))->toBeTrue();
});

it('rejects duplicate emails', function (): void {
    $this->commands->dispatch(new RegisterUser(UserId::generate()->toString(), 'jane@example.com', 'correct horse battery'));

    $this->commands->dispatch(new RegisterUser(UserId::generate()->toString(), 'JANE@example.com', 'correct horse battery'));
})->throws(EmailAlreadyRegistered::class);

it('rejects weak passwords', function (): void {
    $this->commands->dispatch(new RegisterUser(UserId::generate()->toString(), 'jane@example.com', 'short'));
})->throws(WeakPassword::class);

it('reports unknown users', function (): void {
    $this->queries->ask(new GetUser(UserId::generate()->toString()));
})->throws(UserNotFound::class);

it('rejects malformed ids before hitting the database', function (): void {
    $this->queries->ask(new GetUser('not-a-uuid'));
})->throws(InvalidIdentifier::class);
