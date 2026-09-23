<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use DateTimeImmutable;
use SensitiveParameter;

final readonly class SessionTokens
{
    public function __construct(
        public string $userId,
        #[SensitiveParameter]
        public string $accessToken,
        public DateTimeImmutable $accessTokenExpiresAt,
        #[SensitiveParameter]
        public string $refreshToken,
        public DateTimeImmutable $refreshTokenExpiresAt,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['userId' => $this->userId, 'accessToken' => '********', 'refreshToken' => '********'];
    }
}
