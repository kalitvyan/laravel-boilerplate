<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Identity\Application\Authentication\InvalidCredentials;
use LaravelBoilerplate\Identity\Application\Authentication\InvalidRefreshToken;
use LaravelBoilerplate\Identity\Application\Authentication\LogIn;
use LaravelBoilerplate\Identity\Application\Authentication\LogInService;
use LaravelBoilerplate\Identity\Application\Authentication\LogOutService;
use LaravelBoilerplate\Identity\Application\Authentication\RefreshSessionService;
use LaravelBoilerplate\Identity\Application\Authentication\UserIsBlocked;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUser;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\PersonalAccessToken;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;

beforeEach(function (): void {
    $this->password = 'correct horse battery staple';
    $this->userId = UserId::generate()->toString();

    $this->app->make(CommandBus::class)->dispatch(
        new RegisterUser($this->userId, 'jane@example.com', $this->password),
    );

    $this->logIn = $this->app->make(LogInService::class);
    $this->refresh = $this->app->make(RefreshSessionService::class);
    $this->logOut = $this->app->make(LogOutService::class);
});

it('issues a working access token and a refresh token', function (): void {
    $tokens = ($this->logIn)(new LogIn('JANE@example.com', $this->password));

    $accessToken = PersonalAccessToken::findToken($tokens->accessToken);

    expect($tokens->userId)->toBe($this->userId)
        ->and($accessToken)->not->toBeNull()
        ->and((string) $accessToken->tokenable_id)->toBe($this->userId)
        ->and($accessToken->expires_at?->toDateTimeImmutable())->toEqual($tokens->accessTokenExpiresAt)
        // В базе только хеш: сам секрет восстановить нельзя
        ->and(DB::table('identity.refresh_tokens')->where('token_hash', hash('sha256', $tokens->refreshToken))->exists())
        ->toBeTrue()
        ->and(DB::table('identity.refresh_tokens')->where('token_hash', $tokens->refreshToken)->exists())
        ->toBeFalse();
});

it('rejects wrong password and unknown email the same way', function (string $email, string $password): void {
    ($this->logIn)(new LogIn($email, $password));
})->with([
    'wrong password' => ['jane@example.com', 'wrong password here'],
    'unknown email' => ['nobody@example.com', 'correct horse battery staple'],
    'malformed email' => ['not-an-email', 'correct horse battery staple'],
    'too short password' => ['jane@example.com', 'short'],
])->throws(InvalidCredentials::class);

it('rejects blocked users', function (): void {
    $users = $this->app->make(UserRepository::class);
    $user = $users->find(UserId::fromString($this->userId));
    $user->block(new DateTimeImmutable);
    $users->save($user);

    ($this->logIn)(new LogIn('jane@example.com', $this->password));
})->throws(UserIsBlocked::class);

it('rotates tokens on refresh and keeps the family', function (): void {
    $first = ($this->logIn)(new LogIn('jane@example.com', $this->password));

    $second = ($this->refresh)($first->refreshToken);

    $families = DB::table('identity.refresh_tokens')->distinct()->pluck('family_id');

    expect($second->refreshToken)->not->toBe($first->refreshToken)
        ->and($second->accessToken)->not->toBe($first->accessToken)
        ->and($families)->toHaveCount(1)
        ->and(DB::table('identity.refresh_tokens')->whereNotNull('used_at')->count())->toBe(1);
});

it('refuses to reuse a rotated token and kills the whole family', function (): void {
    $first = ($this->logIn)(new LogIn('jane@example.com', $this->password));
    $second = ($this->refresh)($first->refreshToken);

    expect(fn () => ($this->refresh)($first->refreshToken))->toThrow(InvalidRefreshToken::class);

    // Утечка обнаружена: и новый refresh, и все access-токены пользователя недействительны
    expect(fn () => ($this->refresh)($second->refreshToken))->toThrow(InvalidRefreshToken::class)
        ->and(DB::table('identity.refresh_tokens')->whereNull('revoked_at')->count())->toBe(0)
        ->and(PersonalAccessToken::findToken($second->accessToken))->toBeNull();
});

it('refuses refresh for blocked users', function (): void {
    $tokens = ($this->logIn)(new LogIn('jane@example.com', $this->password));

    $users = $this->app->make(UserRepository::class);
    $user = $users->find(UserId::fromString($this->userId));
    $user->block(new DateTimeImmutable);
    $users->save($user);

    expect(fn () => ($this->refresh)($tokens->refreshToken))->toThrow(InvalidRefreshToken::class);
});

it('revokes the session on logout', function (): void {
    $tokens = ($this->logIn)(new LogIn('jane@example.com', $this->password));
    $accessTokenId = (string) PersonalAccessToken::findToken($tokens->accessToken)?->getKey();

    ($this->logOut)($tokens->refreshToken, $accessTokenId);

    expect(fn () => ($this->refresh)($tokens->refreshToken))->toThrow(InvalidRefreshToken::class)
        ->and(PersonalAccessToken::findToken($tokens->accessToken))->toBeNull();
});

it('is idempotent on logout with an unknown token', function (): void {
    ($this->logOut)('never-existed', null);
})->throwsNoExceptions();

it('prunes long-expired tokens only', function (): void {
    $tokens = ($this->logIn)(new LogIn('jane@example.com', $this->password));

    DB::table('identity.refresh_tokens')->update(['expires_at' => now()->subDays(30)]);

    $this->artisan('identity:prune-refresh-tokens', ['--days' => '7'])->assertSuccessful();

    expect(DB::table('identity.refresh_tokens')->count())->toBe(0)
        ->and($tokens->refreshToken)->not->toBeEmpty();
});
it('persists family revocation even though refresh fails', function (): void {
    $first = ($this->logIn)(new LogIn('jane@example.com', $this->password));
    ($this->refresh)($first->refreshToken);

    expect(fn () => ($this->refresh)($first->refreshToken))->toThrow(InvalidRefreshToken::class);

    // Ключевая проверка: откат транзакции сценария не должен отменить отзыв
    expect(DB::table('identity.refresh_tokens')->whereNull('revoked_at')->count())->toBe(0);
});
