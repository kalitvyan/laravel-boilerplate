<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Http;

use Closure;
use Illuminate\Http\Request;
use LaravelBoilerplate\Identity\Application\Port\PrincipalFactory;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\PersonalAccessToken;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use LaravelBoilerplate\Shared\Application\Bus\ActorContext;
use LaravelBoilerplate\Shared\Application\Exception\Unauthenticated;
use Symfony\Component\HttpFoundation\Response;

/**
 * Единственное место, где Laravel auth превращается в Principal.
 * Дальше по коду ни Auth::user(), ни $request->user() не используются.
 */
final readonly class ResolvePrincipal
{
    public function __construct(private PrincipalFactory $principals) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof UserModel) {
            throw new Unauthenticated('Authentication required');
        }

        // Токен ищем сами, а не через currentAccessToken(): так тип известен точно,
        // а запрос уже прогрет кешем модели из guard'а
        $bearer = $request->bearerToken();
        $token = $bearer !== null ? PersonalAccessToken::findToken($bearer) : null;
        $tokenId = $token?->getKey();

        $request->attributes->set(ActorContext::ATTRIBUTE, $this->principals->forUser(
            UserId::fromString($user->id),
            $this->abilitiesOf($token),
            is_int($tokenId) || is_string($tokenId) ? (string) $tokenId : null,
        ));

        return $next($request);
    }

    /**
     * Нет токена — значит, аутентификация прошла иначе (cookie-режим Sanctum),
     * и сужать права нечем.
     *
     * @return list<string>
     */
    private function abilitiesOf(?PersonalAccessToken $token): array
    {
        $abilities = $token?->getAttribute('abilities');

        if (! is_array($abilities)) {
            return ['*'];
        }

        $result = [];

        foreach ($abilities as $ability) {
            if (is_string($ability)) {
                $result[] = $ability;
            }
        }

        return $result === [] ? ['*'] : $result;
    }
}
