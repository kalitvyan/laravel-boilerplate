<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Presentation\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelBoilerplate\Identity\Application\Access\BlockUser;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Presentation\Http\CurrentPrincipal;

final readonly class BlockUserController
{
    public function __construct(private CommandBus $commands) {}

    public function __invoke(Request $request, string $userId): JsonResponse
    {
        // Право проверит middleware шины по атрибуту команды
        $this->commands->dispatch(new BlockUser($userId, CurrentPrincipal::of($request)));

        return new JsonResponse(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
