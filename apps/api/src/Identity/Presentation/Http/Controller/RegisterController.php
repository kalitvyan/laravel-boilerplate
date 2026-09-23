<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Presentation\Http\Controller;

use Illuminate\Http\JsonResponse;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUser;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Presentation\Http\Request\RegisterUserRequest;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;

final readonly class RegisterController
{
    public function __construct(private CommandBus $commands) {}

    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        // ID генерируется до команды: шина ничего не возвращает
        $userId = UserId::generate();

        $this->commands->dispatch(new RegisterUser(
            $userId->toString(),
            $request->email(),
            $request->password(),
        ));

        return new JsonResponse(['id' => $userId->toString()], JsonResponse::HTTP_CREATED);
    }
}
