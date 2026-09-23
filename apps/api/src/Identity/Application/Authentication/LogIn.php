<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use SensitiveParameter;

final readonly class LogIn
{
    public function __construct(
        public string $email,
        #[SensitiveParameter]
        public string $password,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['email' => $this->email, 'password' => '********'];
    }
}
