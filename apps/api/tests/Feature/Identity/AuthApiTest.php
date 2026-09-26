<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use LaravelBoilerplate\Identity\Application\Access\AssignRole;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Domain\Access\Principal;

beforeEach(function (): void {
    // Лимитеры проверяются отдельным тестом; здесь они только мешают
    RateLimiter::for('login', static fn (): Limit => Limit::none());
    RateLimiter::for('refresh', static fn (): Limit => Limit::none());

    $this->password = 'correct horse battery staple';

    $this->register = fn (string $email = 'jane@example.com'): string => $this
        ->postJson('/api/v1/users', ['email' => $email, 'password' => $this->password])
        ->assertCreated()
        ->json('id');

    $this->logIn = fn (string $email = 'jane@example.com'): array => $this
        ->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $this->password])
        ->assertOk()
        ->json();
});

it('registers, logs in and returns the current user', function (): void {
    $id = ($this->register)();
    $session = ($this->logIn)();

    expect($session['userId'])->toBe($id);

    $this->withToken($session['accessToken'])
        ->getJson('/api/v1/users/me')
        ->assertOk()
        ->assertJsonPath('id', $id)
        ->assertJsonPath('email', 'jane@example.com')
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('roles', ['user']);
});

it('rejects requests without a token', function (): void {
    // Запрос намеренно нарушает требование bearerAuth
    $this->withoutContractValidation()
        ->getJson('/api/v1/users/me')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('code', 'unauthenticated');
});

it('reports conflicting registration', function (): void {
    ($this->register)();

    $this->postJson('/api/v1/users', ['email' => 'jane@example.com', 'password' => $this->password])
        ->assertStatus(409)
        ->assertJsonPath('code', 'identity.email_already_registered');
});

it('validates registration input', function (): void {
    // Пароль намеренно короче, чем позволяет схема запроса
    $this->withoutContractValidation()
        ->postJson('/api/v1/users', ['email' => 'jane@example.com', 'password' => 'short'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.0.pointer', '/password');
});

it('answers invalid credentials the same way for unknown and wrong', function (array $payload): void {
    ($this->register)();

    $this->postJson('/api/v1/auth/login', $payload)
        ->assertUnauthorized()
        ->assertJsonPath('code', 'identity.invalid_credentials');
})->with([
    'wrong password' => [['email' => 'jane@example.com', 'password' => 'wrong password here']],
    'unknown email' => [['email' => 'nobody@example.com', 'password' => 'correct horse battery staple']],
]);

it('rotates the session on refresh and invalidates the old token', function (): void {
    ($this->register)();
    $first = ($this->logIn)();

    $second = $this->postJson('/api/v1/auth/refresh', ['refreshToken' => $first['refreshToken']])
        ->assertOk()
        ->json();

    expect($second['refreshToken'])->not->toBe($first['refreshToken']);

    // Повтор в пределах окна грейса — законная гонка двух вкладок
    $this->postJson('/api/v1/auth/refresh', ['refreshToken' => $first['refreshToken']])->assertOk();

    // За пределами окна тот же токен означает утечку
    DB::table('identity.refresh_tokens')->whereNotNull('used_at')->update(['used_at' => now()->subMinute()]);

    $this->postJson('/api/v1/auth/refresh', ['refreshToken' => $first['refreshToken']])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'identity.invalid_refresh_token');
});

it('logs out and kills the session', function (): void {
    ($this->register)();
    $session = ($this->logIn)();

    $this->withToken($session['accessToken'])
        ->postJson('/api/v1/auth/logout', ['refreshToken' => $session['refreshToken']])
        ->assertNoContent();

    $this->withToken($session['accessToken'])->getJson('/api/v1/users/me')->assertUnauthorized();
});

it('throttles password guessing', function (): void {
    expect(config('cache.limiter'))->toBe('array');

    // Глобальный api-лимитер не должен вмешиваться: проверяем именно защиту логина
    RateLimiter::for('api', static fn (): Limit => Limit::perMinute(1000)->by('test-api'));
    RateLimiter::for('login', static fn (Request $request): Limit => Limit::perMinute(5)
        ->by('login-test:'.mb_strtolower((string) $request->input('email'))));

    ($this->register)('throttle-probe@example.com');

    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/v1/auth/login', ['email' => 'throttle-probe@example.com', 'password' => 'wrong password here'])
            ->assertUnauthorized();
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'throttle-probe@example.com', 'password' => $this->password])
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('code', 'too_many_requests');
});

it('enforces permissions on blocking', function (): void {
    $targetId = ($this->register)('john@example.com');
    ($this->register)();
    $session = ($this->logIn)();

    $this->withToken($session['accessToken'])
        ->postJson("/api/v1/users/{$targetId}/block")
        ->assertForbidden()
        ->assertJsonPath('code', 'access_denied');
});

it('blocks a user and revokes their session', function (): void {
    $targetId = ($this->register)('john@example.com');
    $targetSession = ($this->logIn)('john@example.com');

    $adminId = ($this->register)('admin@example.com');
    $this->app->make(CommandBus::class)->dispatch(new AssignRole($adminId, 'admin', Principal::system()));
    $adminSession = ($this->logIn)('admin@example.com');

    $this->withToken($adminSession['accessToken'])
        ->postJson("/api/v1/users/{$targetId}/block")
        ->assertNoContent();

    $this->withToken($targetSession['accessToken'])->getJson('/api/v1/users/me')->assertUnauthorized();

    expect(DB::table('outbox_messages')->where('event_name', 'identity.user_blocked')->exists())->toBeTrue();
});

it('publishes a registration integration event', function (): void {
    $id = ($this->register)();

    $row = DB::table('outbox_messages')->where('event_name', 'identity.user_registered')->sole();

    expect($row->aggregate_id)->toBe($id)
        ->and(json_decode((string) $row->payload, true))->toEqualCanonicalizing(['userId' => $id, 'email' => 'jane@example.com']);
});
