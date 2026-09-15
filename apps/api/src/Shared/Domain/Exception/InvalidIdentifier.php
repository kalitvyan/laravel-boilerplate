<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Domain\Exception;

final class InvalidIdentifier extends DomainError
{
    public static function for(string $value): self
    {
        return new self(sprintf('"%s" is not a valid identifier', $value));
    }
}
