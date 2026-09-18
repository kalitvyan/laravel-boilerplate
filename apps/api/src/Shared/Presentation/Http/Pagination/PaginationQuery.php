<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Pagination;

use Illuminate\Http\Request;
use LaravelBoilerplate\Shared\Application\Pagination\Cursor;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidCursor;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidPageLimit;
use LaravelBoilerplate\Shared\Application\Pagination\PageRequest;

final readonly class PaginationQuery
{
    public static function fromRequest(Request $request): PageRequest
    {
        $limit = $request->query('limit');
        $cursor = $request->query('cursor');

        if ($limit !== null && (! is_string($limit) || preg_match('/^\d{1,3}$/', $limit) !== 1)) {
            throw InvalidPageLimit::outOfRange();
        }

        if ($cursor !== null && ! is_string($cursor)) {
            throw InvalidCursor::malformed();
        }

        return PageRequest::of(
            $limit === null ? PageRequest::DEFAULT_LIMIT : (int) $limit,
            $cursor === null || $cursor === '' ? null : Cursor::decode($cursor),
        );
    }
}
