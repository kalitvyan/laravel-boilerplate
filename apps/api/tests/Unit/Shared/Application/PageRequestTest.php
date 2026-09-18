<?php

declare(strict_types=1);

use LaravelBoilerplate\Shared\Application\Pagination\InvalidPageLimit;
use LaravelBoilerplate\Shared\Application\Pagination\PageRequest;

it('accepts limits within range', function (int $limit): void {
    expect(PageRequest::of($limit)->limit)->toBe($limit);
})->with([1, 20, 100]);

it('rejects limits out of range', function (int $limit): void {
    PageRequest::of($limit);
})->with([0, -1, 101])->throws(InvalidPageLimit::class);
