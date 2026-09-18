<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Pagination;

use LaravelBoilerplate\Shared\Application\Exception\InvalidInput;

final class InvalidCursor extends InvalidInput
{
    public static function malformed(): self
    {
        return new self('Cursor is malformed');
    }

    public static function mismatch(): self
    {
        return new self('Cursor does not match the requested ordering');
    }
}
