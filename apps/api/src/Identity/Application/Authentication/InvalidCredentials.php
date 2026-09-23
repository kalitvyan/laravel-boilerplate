<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use LaravelBoilerplate\Shared\Application\Exception\Unauthenticated;

final class InvalidCredentials extends Unauthenticated
{
    // Одно сообщение для «нет такого email» и «неверный пароль»: иначе API становится
    // оракулом для проверки существования аккаунтов
    public static function create(): self
    {
        return new self('Invalid email or password');
    }
}
