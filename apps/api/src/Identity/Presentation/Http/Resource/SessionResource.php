<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Presentation\Http\Resource;

use Illuminate\Http\JsonResponse;
use LaravelBoilerplate\Identity\Application\Authentication\SessionTokens;

final readonly class SessionResource
{
    public static function toResponse(SessionTokens $tokens): JsonResponse
    {
        return new JsonResponse(
            [
                'userId' => $tokens->userId,
                'accessToken' => $tokens->accessToken,
                'accessTokenExpiresAt' => $tokens->accessTokenExpiresAt->format(DATE_RFC3339_EXTENDED),
                'refreshToken' => $tokens->refreshToken,
                'refreshTokenExpiresAt' => $tokens->refreshTokenExpiresAt->format(DATE_RFC3339_EXTENDED),
            ],
            JsonResponse::HTTP_OK,
            // Токены не должны оседать в промежуточных кэшах
            ['Cache-Control' => 'no-store'],
        );
    }
}
