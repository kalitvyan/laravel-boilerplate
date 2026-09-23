<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus;

final readonly class ActorContext
{
    /**
     * Ключ атрибута запроса, под которым инфраструктура кладёт Principal,
     * а Presentation его читает.
     */
    public const string ATTRIBUTE = 'principal';
}
