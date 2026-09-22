<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Storage;

use LaravelBoilerplate\Shared\Application\Exception\NotFound;

final class StoredFileNotFound extends NotFound
{
    public static function at(StoragePath $path): self
    {
        return new self(sprintf('File "%s" was not found', $path->toString()));
    }
}
