<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Provider;

use DateInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use LaravelBoilerplate\Identity\Application\Access\AssignDefaultRoleOnUserRegistered;
use LaravelBoilerplate\Identity\Application\Access\AssignRole;
use LaravelBoilerplate\Identity\Application\Access\AssignRoleHandler;
use LaravelBoilerplate\Identity\Application\Access\BlockUser;
use LaravelBoilerplate\Identity\Application\Access\BlockUserHandler;
use LaravelBoilerplate\Identity\Application\Access\RevokeTokensOnUserBlocked;
use LaravelBoilerplate\Identity\Application\Authentication\InvalidCredentials;
use LaravelBoilerplate\Identity\Application\Authentication\InvalidRefreshToken;
use LaravelBoilerplate\Identity\Application\Authentication\SessionIssuer;
use LaravelBoilerplate\Identity\Application\Authentication\UserIsBlocked;
use LaravelBoilerplate\Identity\Application\Event\UserBlockedTranslator;
use LaravelBoilerplate\Identity\Application\Event\UserRegisteredTranslator;
use LaravelBoilerplate\Identity\Application\GetUser\GetUser;
use LaravelBoilerplate\Identity\Application\GetUser\GetUserHandler;
use LaravelBoilerplate\Identity\Application\Port\AccessTokenIssuer;
use LaravelBoilerplate\Identity\Application\Port\AccessTokenRevoker;
use LaravelBoilerplate\Identity\Application\Port\PasswordHasher;
use LaravelBoilerplate\Identity\Application\Port\PrincipalFactory;
use LaravelBoilerplate\Identity\Application\Port\SecretGenerator;
use LaravelBoilerplate\Identity\Application\Port\SecretHasher;
use LaravelBoilerplate\Identity\Application\ReadModel\UserReadModel;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUser;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUserHandler;
use LaravelBoilerplate\Identity\Application\UserNotFound;
use LaravelBoilerplate\Identity\Domain\Access\RoleCatalog;
use LaravelBoilerplate\Identity\Domain\Access\UnknownRole;
use LaravelBoilerplate\Identity\Domain\Access\UserRoleRepository;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenRepository;
use LaravelBoilerplate\Identity\Domain\User\EmailAlreadyRegistered;
use LaravelBoilerplate\Identity\Domain\User\Event\UserBlocked;
use LaravelBoilerplate\Identity\Domain\User\Event\UserRegistered;
use LaravelBoilerplate\Identity\Domain\User\InvalidEmail;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Identity\Domain\User\WeakPassword;
use LaravelBoilerplate\Identity\Infrastructure\Access\ConfigRoleCatalog;
use LaravelBoilerplate\Identity\Infrastructure\Access\DatabaseUserRoleRepository;
use LaravelBoilerplate\Identity\Infrastructure\Access\DefaultPrincipalFactory;
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
use LaravelBoilerplate\Shared\Infrastructure\Event\DomainEventListenerMap;
use LaravelBoilerplate\Shared\Infrastructure\Event\DomainEventTranslatorMap;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemDefinition;
use LaravelBoilerplate\Shared\Presentation\Http\Problem\ProblemMap;
use LogicException;
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
            UnknownRole::class => new ProblemDefinition(422, 'identity.unknown_role', 'Unknown role'),
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

        $this->app->singleton(RoleCatalog::class, fn (): RoleCatalog => new ConfigRoleCatalog(
            $this->stringList('identity.rbac.permissions'),
            $this->roleDefinitions(),
        ));

        $this->app->bind(UserRoleRepository::class, DatabaseUserRoleRepository::class);
        $this->app->bind(PrincipalFactory::class, DefaultPrincipalFactory::class);

        $this->app->bind(AssignDefaultRoleOnUserRegistered::class, static fn (Application $app): AssignDefaultRoleOnUserRegistered => new AssignDefaultRoleOnUserRegistered(
            $app->make(UserRoleRepository::class),
            config()->string('identity.rbac.default_role'),
            $app->make(ClockInterface::class),
        ));

        $this->app->extend(CommandHandlerMap::class, static fn (CommandHandlerMap $map): CommandHandlerMap => $map->with([
            AssignRole::class => AssignRoleHandler::class,
            BlockUser::class => BlockUserHandler::class,
        ]));

        $this->app->extend(DomainEventListenerMap::class, static fn (DomainEventListenerMap $map): DomainEventListenerMap => $map->with([
            UserRegistered::class => [AssignDefaultRoleOnUserRegistered::class],
            UserBlocked::class => [RevokeTokensOnUserBlocked::class],
        ]));

        $this->app->extend(DomainEventTranslatorMap::class, static fn (DomainEventTranslatorMap $map): DomainEventTranslatorMap => $map->with([
            UserRegistered::class => UserRegisteredTranslator::class,
            UserBlocked::class => UserBlockedTranslator::class,
        ]));
    }

    public function boot(): void
    {
        // Морф-карта: в tokenable_type хранится стабильный алиас, а не имя PHP-класса
        Relation::enforceMorphMap(['identity.user' => UserModel::class]);

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneRefreshTokensCommand::class]);
        }

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../../Presentation/Http/routes.php');

        RateLimiter::for('login', static function (Request $request): Limit {
            $email = $request->input('email');

            // Ключ по email + IP: смена адреса не обходит лимит, а чужой запрос
            // не расходует лимит легитимного пользователя
            return Limit::perMinute(5)->by(sprintf(
                'login:%s:%s',
                is_string($email) ? mb_strtolower($email) : 'unknown',
                $request->ip() ?? 'unknown',
            ));
        });

        RateLimiter::for('refresh', static fn (Request $request): Limit => Limit::perMinute(30)->by('refresh:'.$request->ip()));
    }

    /**
     * @return array<string, list<string>>
     */
    private function roleDefinitions(): array
    {
        $roles = config()->array('identity.rbac.roles');
        $definitions = [];

        foreach ($roles as $name => $permissions) {
            if (! is_string($name) || ! is_array($permissions)) {
                throw new LogicException('Malformed identity.rbac.roles configuration');
            }

            $definitions[$name] = $this->toStringList($permissions, sprintf('identity.rbac.roles.%s', $name));
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    private function stringList(string $key): array
    {
        return $this->toStringList(config()->array($key), $key);
    }

    /**
     * @param  array<mixed>  $values
     * @return list<string>
     */
    private function toStringList(array $values, string $key): array
    {
        foreach ($values as $value) {
            if (! is_string($value)) {
                throw new LogicException(sprintf('Configuration "%s" must contain only strings', $key));
            }
        }

        return array_values(array_filter($values, is_string(...)));
    }
}
