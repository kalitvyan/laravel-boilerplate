<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use LaravelBoilerplate\Identity\Application\GetUser\GetUser;
use LaravelBoilerplate\Identity\Application\GetUser\GetUserHandler;
use LaravelBoilerplate\Identity\Application\Port\PasswordHasher;
use LaravelBoilerplate\Identity\Application\ReadModel\UserReadModel;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUser;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUserHandler;
use LaravelBoilerplate\Identity\Application\UserNotFound;
use LaravelBoilerplate\Identity\Domain\User\EmailAlreadyRegistered;
use LaravelBoilerplate\Identity\Domain\User\InvalidEmail;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Identity\Domain\User\WeakPassword;
use LaravelBoilerplate\Identity\Infrastructure\Hashing\LaravelPasswordHasher;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\EloquentUserRepository;
use LaravelBoilerplate\Identity\Infrastructure\ReadModel\DatabaseUserReadModel;
use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Bus\QueryHandlerMap;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemDefinition;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemMap;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(UserReadModel::class, DatabaseUserReadModel::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);

        $this->app->extend(CommandHandlerMap::class, static fn (CommandHandlerMap $map): CommandHandlerMap => $map->with([
            RegisterUser::class => RegisterUserHandler::class,
        ]));

        $this->app->extend(QueryHandlerMap::class, static fn (QueryHandlerMap $map): QueryHandlerMap => $map->with([
            GetUser::class => GetUserHandler::class,
        ]));

        $this->app->extend(ProblemMap::class, static fn (ProblemMap $map): ProblemMap => $map->with([
            InvalidEmail::class => new ProblemDefinition(422, 'identity.invalid_email', 'Invalid email'),
            WeakPassword::class => new ProblemDefinition(422, 'identity.weak_password', 'Password does not meet requirements'),
            EmailAlreadyRegistered::class => new ProblemDefinition(409, 'identity.email_already_registered', 'Email already registered'),
            UserNotFound::class => new ProblemDefinition(404, 'identity.user_not_found', 'User not found'),
        ]));
    }
}
