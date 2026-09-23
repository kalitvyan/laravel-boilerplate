<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Presentation\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelBoilerplate\Identity\Application\GetUser\GetUser;
use LaravelBoilerplate\Identity\Presentation\Http\Resource\UserResource;
use LaravelBoilerplate\Shared\Application\Bus\QueryBus;
use LaravelBoilerplate\Shared\Presentation\Http\CurrentPrincipal;

final readonly class CurrentUserController
{
    public function __construct(private QueryBus $queries) {}

    public function __invoke(Request $request): JsonResponse
    {
        $principal = CurrentPrincipal::of($request);

        return new JsonResponse(UserResource::toArray(
            $this->queries->ask(new GetUser($principal->id)),
            $principal,
        ));
    }
}
