<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelBoilerplate\Shared\Presentation\Http\Pagination\PaginationQuery;

beforeEach(function (): void {
    $this->withoutContractValidation();

    Route::get('/_test/page', static fn (Request $request): array => [
        'limit' => PaginationQuery::fromRequest($request)->limit,
    ]);
});

it('uses default limit', function (): void {
    $this->getJson('/_test/page')->assertExactJson(['limit' => 20]);
});

it('rejects invalid limit', function (string $limit): void {
    $this->getJson('/_test/page?limit='.urlencode($limit))
        ->assertStatus(400)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('code', 'pagination.invalid_limit');
})->with(['0', '101', 'abc', '-1', '1.5']);

it('rejects malformed cursor', function (): void {
    $this->getJson('/_test/page?cursor=***')
        ->assertStatus(400)
        ->assertJsonPath('code', 'pagination.invalid_cursor');
});
