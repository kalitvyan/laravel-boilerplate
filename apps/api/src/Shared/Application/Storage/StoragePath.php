<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Storage;

/**
 * Ключ объекта в хранилище. Относительный, без обхода каталогов и управляющих символов:
 * части пути нередко приходят от пользователя (имя файла).
 */
final readonly class StoragePath
{
    private const int MAX_LENGTH = 1024;

    private function __construct(private string $value) {}

    public static function of(string $path): self
    {
        if (
            $path === ''
            || strlen($path) > self::MAX_LENGTH
            || $path !== trim($path)
            || str_starts_with($path, '/')
            || str_contains($path, '\\')
            || preg_match('#(^|/)\.{1,2}(/|$)#', $path) === 1
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
        ) {
            throw InvalidStoragePath::for($path);
        }

        return new self($path);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
