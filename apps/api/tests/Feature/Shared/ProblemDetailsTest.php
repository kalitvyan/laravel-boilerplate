<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use LaravelBoilerplate\Shared\Application\Exception\NotFound;

beforeEach(function (): void {
    Route::get('/_test/not-found', static fn () => throw new class('Order was not found') extends NotFound {});
    Route::get('/_test/crash', static fn () => throw new RuntimeException('secret internals'));
    Route::post('/_test/validation', static fn (Request $request) => $request->validate([
        'email' => ['required', 'email'],
    ]));
});

it('maps application exceptions via ProblemMap, including subclasses', function (): void {
    $requestId = '0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d';

    $this->withHeader('X-Request-Id', $requestId)
        ->getJson('/_test/not-found')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertHeader('X-Request-Id', $requestId)
        ->assertJson([
            'type' => 'about:blank',
            'status' => 404,
            'code' => 'not_found',
            'detail' => 'Order was not found',
            'instance' => '/_test/not-found',
            'traceId' => $requestId,
        ]);
});

it('hides internals of unmapped exceptions when debug is off', function (): void {
    config(['app.debug' => false]);

    $this->getJson('/_test/crash')
        ->assertStatus(500)
        ->assertJsonPath('code', 'internal_error')
        ->assertJsonMissingPath('detail')
        ->assertJsonMissingPath('exception');
});

it('renders validation errors with JSON pointers and rule codes', function (): void {
    $this->postJson('/_test/validation', ['email' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.0.pointer', '/email')
        ->assertJsonPath('errors.0.code', 'email');
});

it('renders unknown routes as problem', function (): void {
    $this->getJson('/definitely-missing')
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('uses configured base url for problem type', function (): void {
    config(['api.problem_type_base_url' => 'https://api.example.com/problems/']);

    $this->getJson('/_test/not-found')
        ->assertJsonPath('type', 'https://api.example.com/problems/not-found');
});

it('rejects non-UUID request ids', function (): void {
    $response = $this->withHeader('X-Request-Id', "evil\nlog")->getJson('/health/live');

    expect($response->headers->get('X-Request-Id'))->not->toBe("evil\nlog");
});

it('does not report client errors but reports unexpected ones', function (): void {
    Exceptions::fake();

    $this->getJson('/_test/not-found')->assertNotFound();
    $this->getJson('/_test/crash')->assertStatus(500);

    Exceptions::assertNotReported(NotFound::class);
    Exceptions::assertReported(RuntimeException::class);
});
