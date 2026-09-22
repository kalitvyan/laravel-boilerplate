<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Storage;

use LaravelBoilerplate\Shared\Application\Exception\InvalidInput;

final class InvalidStoragePath extends InvalidInput
{
    public static function for(string $path): self
    {
        return new self(sprintf('"%s" is not a valid storage path', $path));
    }
}
