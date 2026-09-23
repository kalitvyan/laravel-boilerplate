<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http;

use Illuminate\Http\Request;
use LaravelBoilerplate\Shared\Application\Bus\ActorContext;
use LaravelBoilerplate\Shared\Application\Exception\Unauthenticated;
use LaravelBoilerplate\Shared\Domain\Access\Principal;

final readonly class CurrentPrincipal
{
    public static function of(Request $request): Principal
    {
        $principal = $request->attributes->get(ActorContext::ATTRIBUTE);

        return $principal instanceof Principal
            ? $principal
            : throw new Unauthenticated('Authentication required');
    }
}
