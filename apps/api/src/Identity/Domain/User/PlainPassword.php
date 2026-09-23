<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User;

use LogicException;
use SensitiveParameter;

/**
 * Пароль в открытом виде живёт только в пределах запроса: не попадает в дампы, стектрейсы и сериализацию.
 */
final readonly class PlainPassword
{
    public const int MIN_LENGTH = 12;

    // bcrypt молча обрезает ввод после 72 байт: длинный пароль дал бы ложное ощущение стойкости
    public const int MAX_BYTES = 72;

    private function __construct(
        #[SensitiveParameter]
        private string $value,
    ) {}

    public static function fromString(#[SensitiveParameter] string $password): self
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            throw WeakPassword::tooShort(self::MIN_LENGTH);
        }

        if (strlen($password) > self::MAX_BYTES) {
            throw WeakPassword::tooLong(self::MAX_BYTES);
        }

        return new self($password);
    }

    public function reveal(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '********'];
    }

    /**
     * @return never
     */
    public function __serialize(): array
    {
        throw new LogicException('Plain password must never be serialized');
    }
}
