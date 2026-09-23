<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class RefreshSessionRequest extends FormRequest
{
    /**
     * @return array<string, list<string|int>>
     */
    public function rules(): array
    {
        return [
            'refreshToken' => ['required', 'string', 'max:512'],
        ];
    }

    public function refreshToken(): string
    {
        return $this->string('refreshToken')->toString();
    }
}
