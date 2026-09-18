<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Pagination;

final readonly class PageRequest
{
    public const int DEFAULT_LIMIT = 20;

    public const int MAX_LIMIT = 100;

    private function __construct(
        public int $limit,
        public ?Cursor $cursor,
    ) {}

    public static function of(int $limit = self::DEFAULT_LIMIT, ?Cursor $cursor = null): self
    {
        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            throw InvalidPageLimit::outOfRange();
        }

        return new self($limit, $cursor);
    }
}
