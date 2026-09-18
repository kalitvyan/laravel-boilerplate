<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaravelBoilerplate\Shared\Application\Pagination\Cursor;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidCursor;
use LaravelBoilerplate\Shared\Application\Pagination\PageRequest;
use LaravelBoilerplate\Shared\Infrastructure\Pagination\KeysetPaginator;
use Symfony\Component\Uid\Uuid;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    // DDL в Postgres транзакционен: таблица откатится вместе с тестом
    Schema::create('pagination_fixtures', static function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->timestampTz('created_at');
    });

    $this->ids = [];

    for ($i = 0; $i < 5; $i++) {
        $id = Uuid::v7()->toRfc4122();
        $this->ids[] = $id;

        DB::table('pagination_fixtures')->insert(['id' => $id, 'created_at' => '2026-01-01 00:00:00+00']);
    }

    // Порядок lowercase-hex строк совпадает с порядком uuid в Postgres
    rsort($this->ids);
});

it('walks all rows without gaps or duplicates', function (array $orderBy): void {
    $paginator = new KeysetPaginator;
    $seen = [];
    $cursor = null;
    $pages = 0;

    do {
        $page = $paginator->paginate(
            DB::table('pagination_fixtures')->select(['id', 'created_at']),
            $orderBy,
            PageRequest::of(2, $cursor),
            static fn (stdClass $row): string => (string) $row->id,
        );

        $seen = [...$seen, ...$page->items];
        // Эмулируем реальный путь курсора через HTTP
        $cursor = $page->nextCursor instanceof Cursor ? Cursor::decode($page->nextCursor->encode()) : null;
        $pages++;
    } while ($cursor instanceof Cursor);

    expect($seen)->toBe($this->ids)
        ->and($pages)->toBe(3);
})->with([
    'id only' => [['id' => 'desc']],
    'created_at with id tie-breaker' => [['created_at' => 'desc', 'id' => 'desc']],
]);

it('rejects cursor built for a different ordering', function (): void {
    new KeysetPaginator()->paginate(
        DB::table('pagination_fixtures')->select(['id', 'created_at']),
        ['created_at' => 'desc', 'id' => 'desc'],
        PageRequest::of(2, Cursor::fromPosition(['id' => $this->ids[0]])),
        static fn (stdClass $row): stdClass => $row,
    );
})->throws(InvalidCursor::class);

it('rejects mixed sort directions', function (): void {
    new KeysetPaginator()->paginate(
        DB::table('pagination_fixtures'),
        ['created_at' => 'desc', 'id' => 'asc'],
        PageRequest::of(),
        static fn (stdClass $row): stdClass => $row,
    );
})->throws(LogicException::class, 'same direction');
