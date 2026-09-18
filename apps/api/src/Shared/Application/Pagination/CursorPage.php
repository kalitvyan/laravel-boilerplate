<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Pagination;

/**
 * @template T
 */
final readonly class CursorPage
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public array $items,
        public ?Cursor $nextCursor,
        public int $limit,
    ) {}
}
