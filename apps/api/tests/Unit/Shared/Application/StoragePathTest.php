<?php

declare(strict_types=1);

use LaravelBoilerplate\Shared\Application\Storage\InvalidStoragePath;
use LaravelBoilerplate\Shared\Application\Storage\StoragePath;

it('accepts relative paths', function (string $path): void {
    expect(StoragePath::of($path)->toString())->toBe($path);
})->with([
    'users/0191f2a0/avatar.png',
    'report.final.pdf',
    'a/b/c/d.txt',
]);

it('rejects unsafe paths', function (string $path): void {
    StoragePath::of($path);
})->with([
    'empty' => [''],
    'absolute' => ['/etc/passwd'],
    'parent traversal' => ['../secret'],
    'nested traversal' => ['a/../../b'],
    'current dir segment' => ['a/./b'],
    'backslash' => ['a\\b'],
    'null byte' => ["a\0b"],
    'newline' => ["a\nb"],
    'surrounding spaces' => [' a.txt '],
])->throws(InvalidStoragePath::class);
