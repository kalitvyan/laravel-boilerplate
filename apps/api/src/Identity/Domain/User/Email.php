<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User;

use Stringable;

final readonly class Email implements Stringable
{
    private const int MAX_LENGTH = 254;

    private function __construct(private string $value) {}

    /**
     * Нормализуем к нижнему регистру целиком. Локальная часть по RFC регистрозависима,
     * но на практике провайдеры это игнорируют, а дубли вида John@/john@ — источник проблем.
     */
    public static function fromString(string $email): self
    {
        $normalized = mb_strtolower(trim($email));

        if (
            $normalized === ''
            || strlen($normalized) > self::MAX_LENGTH
            || filter_var($normalized, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw InvalidEmail::create();
        }

        return new self($normalized);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
