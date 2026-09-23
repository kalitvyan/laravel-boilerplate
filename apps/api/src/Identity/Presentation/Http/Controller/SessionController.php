<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Presentation\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelBoilerplate\Identity\Application\Authentication\LogIn;
use LaravelBoilerplate\Identity\Application\Authentication\LogInService;
use LaravelBoilerplate\Identity\Application\Authentication\LogOutService;
use LaravelBoilerplate\Identity\Application\Authentication\RefreshSessionService;
use LaravelBoilerplate\Identity\Presentation\Http\Request\LogInRequest;
use LaravelBoilerplate\Identity\Presentation\Http\Request\RefreshSessionRequest;
use LaravelBoilerplate\Identity\Presentation\Http\Resource\SessionResource;
use LaravelBoilerplate\Shared\Presentation\Http\CurrentPrincipal;

final readonly class SessionController
{
    public function __construct(
        private LogInService $logIn,
        private RefreshSessionService $refresh,
        private LogOutService $logOut,
    ) {}

    public function store(LogInRequest $request): JsonResponse
    {
        return SessionResource::toResponse(($this->logIn)(new LogIn($request->email(), $request->password())));
    }

    public function refresh(RefreshSessionRequest $request): JsonResponse
    {
        return SessionResource::toResponse(($this->refresh)($request->refreshToken()));
    }

    public function destroy(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refreshToken');

        ($this->logOut)(
            is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : null,
            CurrentPrincipal::of($request)->tokenId,
        );

        return new JsonResponse(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
