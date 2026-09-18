<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Pagination;

use LaravelBoilerplate\Shared\Application\Exception\InvalidInput;

final class InvalidPageLimit extends InvalidInput
{
    public static function outOfRange(): self
    {
        return new self(sprintf('Limit must be an integer between 1 and %d', PageRequest::MAX_LIMIT));
    }
}
