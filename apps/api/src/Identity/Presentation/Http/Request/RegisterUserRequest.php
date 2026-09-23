<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use LaravelBoilerplate\Identity\Domain\User\PlainPassword;

/**
 * Формальная проверка входа. Бизнес-правила (формат email, политика пароля)
 * остаются в домене: FormRequest только отсекает мусор до слоя приложения.
 */
final class RegisterUserRequest extends FormRequest
{
    /**
     * @return array<string, list<string|int>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:254'],
            'password' => ['required', 'string', 'min:'.PlainPassword::MIN_LENGTH, 'max:'.PlainPassword::MAX_BYTES],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }

    public function password(): string
    {
        return $this->string('password')->toString();
    }
}
