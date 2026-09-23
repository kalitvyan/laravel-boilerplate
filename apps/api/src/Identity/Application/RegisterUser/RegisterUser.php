<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\RegisterUser;

use LaravelBoilerplate\Shared\Application\Bus\Command;
use SensitiveParameter;

final readonly class RegisterUser implements Command
{
    public function __construct(
        public string $userId,
        public string $email,
        #[SensitiveParameter]
        public string $password,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['userId' => $this->userId, 'email' => $this->email, 'password' => '********'];
    }
}
