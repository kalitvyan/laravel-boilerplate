<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\AssertionFailedError;
use Tests\Support\Contract\OpenApiContract;

it('detects response body that violates the schema', function (): void {
    expect(fn () => OpenApiContract::assertMatches(
        Request::create('/health/live'),
        new JsonResponse(['status' => 'unexpected']),
    ))->toThrow(AssertionFailedError::class, 'contract violation');
});

it('detects undocumented status code', function (): void {
    expect(fn () => OpenApiContract::assertMatches(
        Request::create('/health/live'),
        new JsonResponse(['status' => 'ok'], 418),
    ))->toThrow(AssertionFailedError::class);
});

it('detects undocumented endpoints', function (): void {
    expect(fn () => OpenApiContract::assertMatches(
        Request::create('/undocumented'),
        new JsonResponse([]),
    ))->toThrow(AssertionFailedError::class);
});
