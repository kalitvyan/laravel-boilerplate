<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Pagination;

use Illuminate\Database\Query\Builder;
use LaravelBoilerplate\Shared\Application\Pagination\Cursor;
use LaravelBoilerplate\Shared\Application\Pagination\CursorPage;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidCursor;
use LaravelBoilerplate\Shared\Application\Pagination\PageRequest;
use LogicException;
use stdClass;

final readonly class KeysetPaginator
{
    /**
     * Требования к $orderBy:
     *  - одно направление для всех колонок (сравнение кортежей);
     *  - последняя колонка уникальна (обычно id);
     *  - все колонки NOT NULL и присутствуют в select.
     *
     * @template T
     *
     * @param  non-empty-array<string, 'asc'|'desc'>  $orderBy
     * @param  callable(stdClass): T  $map
     * @return CursorPage<T>
     */
    public function paginate(Builder $query, array $orderBy, PageRequest $page, callable $map): CursorPage
    {
        if (count(array_unique($orderBy)) !== 1) {
            throw new LogicException('Keyset pagination requires the same direction for all columns');
        }

        $direction = array_first($orderBy);
        $columns = array_keys($orderBy);
        $keys = array_map($this->keyOf(...), $columns);

        $query = clone $query;

        if ($page->cursor instanceof Cursor) {
            // TODO: fix it
            $query->whereRaw(...$this->keysetCondition($query, $columns, $keys, $direction, $page->cursor));
        }

        foreach ($orderBy as $column => $columnDirection) {
            $query->orderBy($column, $columnDirection);
        }

        $rows = array_values($query->limit($page->limit + 1)->get()->all());
        $hasMore = count($rows) > $page->limit;
        $rows = array_slice($rows, 0, $page->limit);

        return new CursorPage(
            array_map($map, $rows),
            $hasMore ? $this->cursorFrom($rows, $keys) : null,
            $page->limit,
        );
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $keys
     * @return array{string, list<string|int|float>}
     */
    private function keysetCondition(Builder $query, array $columns, array $keys, string $direction, Cursor $cursor): array
    {
        if (count($cursor->position) !== count($keys)) {
            throw InvalidCursor::mismatch();
        }

        $values = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $cursor->position)) {
                throw InvalidCursor::mismatch();
            }

            $values[] = $cursor->position[$key];
        }

        $grammar = $query->getGrammar();

        $sql = sprintf(
            '(%s) %s (%s)',
            implode(', ', array_map($grammar->wrap(...), $columns)),
            $direction === 'desc' ? '<' : '>',
            implode(', ', array_fill(0, count($columns), '?')),
        );

        return [$sql, $values];
    }

    /**
     * @param  list<stdClass>  $rows
     * @param  list<string>  $keys
     */
    private function cursorFrom(array $rows, array $keys): Cursor
    {
        $last = end($rows);

        if ($last === false) {
            throw new LogicException('Cannot build cursor from an empty page');
        }

        $position = [];

        foreach ($keys as $key) {
            $value = $last->{$key} ?? null;

            if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
                throw new LogicException(sprintf('Keyset column "%s" must be selected and NOT NULL', $key));
            }

            $position[$key] = $value;
        }

        // TODO: fix it
        return Cursor::fromPosition($position);
    }

    private function keyOf(string $column): string
    {
        $dot = strrpos($column, '.');

        return $dot === false ? $column : substr($column, $dot + 1);
    }
}
