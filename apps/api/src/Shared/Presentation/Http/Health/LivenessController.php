<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Health;

use Illuminate\Http\JsonResponse;

final readonly class LivenessController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            data: ['status' => 'ok'],
            headers: ['Cache-Control' => 'no-store']
        );
    }
}
