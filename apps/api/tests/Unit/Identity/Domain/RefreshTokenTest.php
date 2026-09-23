<?php

declare(strict_types=1);

use LaravelBoilerplate\Identity\Domain\Token\RefreshToken;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenId;
use LaravelBoilerplate\Identity\Domain\Token\TokenFamilyId;
use LaravelBoilerplate\Identity\Domain\User\UserId;

$issue = static fn (DateTimeImmutable $expiresAt): RefreshToken => RefreshToken::issue(
    RefreshTokenId::generate(),
    TokenFamilyId::generate(),
    UserId::generate(),
    str_repeat('a', 64),
    $expiresAt,
    new DateTimeImmutable('2026-01-01 10:00:00'),
);

it('is usable while fresh', function () use ($issue): void {
    expect($issue(new DateTimeImmutable('2026-02-01'))->isUsable(new DateTimeImmutable('2026-01-02')))->toBeTrue();
});

it('is not usable when expired, used or revoked', function () use ($issue): void {
    $now = new DateTimeImmutable('2026-01-02');

    $expired = $issue(new DateTimeImmutable('2026-01-01 10:00:01'));

    $used = $issue(new DateTimeImmutable('2026-02-01'));
    $used->markUsed($now);

    $revoked = $issue(new DateTimeImmutable('2026-02-01'));
    $revoked->revoke($now);

    expect($expired->isUsable($now))->toBeFalse()
        ->and($used->isUsable($now))->toBeFalse()
        ->and($used->wasUsed())->toBeTrue()
        ->and($revoked->isUsable($now))->toBeFalse()
        ->and($revoked->wasUsed())->toBeFalse();
});

it('keeps the first timestamps', function () use ($issue): void {
    $token = $issue(new DateTimeImmutable('2026-02-01'));

    $token->markUsed(new DateTimeImmutable('2026-01-02'));
    $token->markUsed(new DateTimeImmutable('2026-01-03'));
    $token->revoke(new DateTimeImmutable('2026-01-04'));
    $token->revoke(new DateTimeImmutable('2026-01-05'));

    expect($token->usedAt()?->format('Y-m-d'))->toBe('2026-01-02')
        ->and($token->revokedAt()?->format('Y-m-d'))->toBe('2026-01-04');
});
