<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Provider;

use DateInterval;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use LaravelBoilerplate\Identity\Application\Authentication\InvalidCredentials;
use LaravelBoilerplate\Identity\Application\Authentication\InvalidRefreshToken;
use LaravelBoilerplate\Identity\Application\Authentication\SessionIssuer;
use LaravelBoilerplate\Identity\Application\Authentication\UserIsBlocked;
use LaravelBoilerplate\Identity\Application\GetUser\GetUser;
use LaravelBoilerplate\Identity\Application\GetUser\GetUserHandler;
use LaravelBoilerplate\Identity\Application\Port\AccessTokenIssuer;
use LaravelBoilerplate\Identity\Application\Port\AccessTokenRevoker;
use LaravelBoilerplate\Identity\Application\Port\PasswordHasher;
use LaravelBoilerplate\Identity\Application\Port\SecretGenerator;
use LaravelBoilerplate\Identity\Application\Port\SecretHasher;
use LaravelBoilerplate\Identity\Application\ReadModel\UserReadModel;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUser;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUserHandler;
use LaravelBoilerplate\Identity\Application\UserNotFound;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenRepository;
use LaravelBoilerplate\Identity\Domain\User\EmailAlreadyRegistered;
use LaravelBoilerplate\Identity\Domain\User\InvalidEmail;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Identity\Domain\User\WeakPassword;
use LaravelBoilerplate\Identity\Infrastructure\Console\PruneRefreshTokensCommand;
use LaravelBoilerplate\Identity\Infrastructure\Hashing\LaravelPasswordHasher;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\DatabaseRefreshTokenRepository;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\PersonalAccessToken;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\EloquentUserRepository;
use LaravelBoilerplate\Identity\Infrastructure\ReadModel\DatabaseUserReadModel;
use LaravelBoilerplate\Identity\Infrastructure\Sanctum\SanctumAccessTokenIssuer;
use LaravelBoilerplate\Identity\Infrastructure\Sanctum\SanctumAccessTokenRevoker;
use LaravelBoilerplate\Identity\Infrastructure\Security\RandomSecretGenerator;
use LaravelBoilerplate\Identity\Infrastructure\Security\Sha256SecretHasher;
use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Bus\QueryHandlerMap;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemDefinition;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemMap;
use Psr\Clock\ClockInterface;

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
            InvalidCredentials::class => new ProblemDefinition(401, 'identity.invalid_credentials', 'Invalid credentials'),
            InvalidRefreshToken::class => new ProblemDefinition(401, 'identity.invalid_refresh_token', 'Invalid refresh token'),
            UserIsBlocked::class => new ProblemDefinition(403, 'identity.user_blocked', 'Account is blocked'),
        ]));

        $this->app->bind(RefreshTokenRepository::class, DatabaseRefreshTokenRepository::class);
        $this->app->bind(AccessTokenIssuer::class, SanctumAccessTokenIssuer::class);
        $this->app->bind(AccessTokenRevoker::class, SanctumAccessTokenRevoker::class);
        $this->app->bind(SecretGenerator::class, RandomSecretGenerator::class);
        $this->app->bind(SecretHasher::class, Sha256SecretHasher::class);

        $this->app->bind(SessionIssuer::class, static fn (Application $app): SessionIssuer => new SessionIssuer(
            $app->make(AccessTokenIssuer::class),
            $app->make(RefreshTokenRepository::class),
            $app->make(SecretGenerator::class),
            $app->make(SecretHasher::class),
            $app->make(ClockInterface::class),
            new DateInterval(config()->string('identity.tokens.access_ttl')),
            new DateInterval(config()->string('identity.tokens.refresh_ttl')),
        ));
    }

    public function boot(): void
    {
        // Морф-карта: в tokenable_type хранится стабильный алиас, а не имя PHP-класса
        Relation::enforceMorphMap(['identity.user' => UserModel::class]);

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneRefreshTokensCommand::class]);
        }
    }
}
