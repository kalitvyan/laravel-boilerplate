<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Sanctum;

use Carbon\CarbonImmutable;
use DateInterval;
use LaravelBoilerplate\Identity\Application\Authentication\IssuedAccessToken;
use LaravelBoilerplate\Identity\Application\Port\AccessTokenIssuer;
use LaravelBoilerplate\Identity\Application\UserNotFound;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use LogicException;
use Psr\Clock\ClockInterface;

final readonly class SanctumAccessTokenIssuer implements AccessTokenIssuer
{
    public function __construct(private ClockInterface $clock) {}

    public function issue(UserId $userId, array $abilities, DateInterval $ttl): IssuedAccessToken
    {
        $model = UserModel::query()->find($userId->toString());

        if (! $model instanceof UserModel) {
            throw UserNotFound::withId($userId->toString());
        }

        $expiresAt = $this->clock->now()->add($ttl);

        $token = $model->createToken('api', $abilities, CarbonImmutable::instance($expiresAt));
        $tokenId = $token->accessToken->getKey();

        if (! is_int($tokenId) && ! is_string($tokenId)) {
            throw new LogicException('Unexpected personal access token key type');
        }

        return new IssuedAccessToken(
            (string) $tokenId,
            $token->plainTextToken,
            $expiresAt,
        );
    }
}
