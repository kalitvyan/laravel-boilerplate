<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Pagination;

use Illuminate\Http\JsonResponse;
use LaravelBoilerplate\Shared\Application\Pagination\CursorPage;

final readonly class CursorPageResponse
{
    /**
     * @template T
     *
     * @param  CursorPage<T>  $page
     * @param  callable(T): array<string, mixed>  $transform
     */
    public static function from(CursorPage $page, callable $transform): JsonResponse
    {
        return new JsonResponse(
            [
                'items' => array_map($transform, $page->items),
                'page' => [
                    'nextCursor' => $page->nextCursor?->encode(),
                    'limit' => $page->limit,
                ],
            ],
            JsonResponse::HTTP_OK,
            [],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
