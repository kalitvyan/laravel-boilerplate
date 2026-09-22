<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Uid\Uuid;

beforeEach(function (): void {
    $this->withoutContractValidation();

    Route::get('/_test/ip', static fn (Request $request): array => ['ip' => $request->ip()]);
});

it('resolves client ip from X-Forwarded-For sent by a trusted proxy', function (): void {
    $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->getJson('/_test/ip')
        ->assertExactJson(['ip' => '203.0.113.7']);
});

it('ignores X-Forwarded-For from untrusted clients', function (): void {
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->getJson('/_test/ip')
        ->assertExactJson(['ip' => '198.51.100.1']);
});

it('keys the api limiter by client ip', function (): void {
    $limiter = RateLimiter::limiter('api');
    $limit = $limiter(Request::create('/api/v1/anything', server: ['REMOTE_ADDR' => '203.0.113.7']));

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->key)->toBe('ip:203.0.113.7')
        ->and($limit->maxAttempts)->toBe(config('api.rate_limit.per_minute'));
});

it('renders throttling as problem details with Retry-After', function (): void {
    // Уникальный лимитер: счётчики в Redis переживают прогоны тестов
    $name = 'test-'.Uuid::v7()->toRfc4122();
    RateLimiter::for($name, static fn (): Limit => Limit::perMinute(1)->by('same-client'));
    Route::middleware('throttle:'.$name)->get('/_test/throttled', static fn (): array => ['ok' => true]);

    $this->getJson('/_test/throttled')->assertOk();

    $this->getJson('/_test/throttled')
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('code', 'too_many_requests');
});
