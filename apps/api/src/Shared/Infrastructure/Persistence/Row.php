<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Persistence;

use DateTimeImmutable;
use JsonException;
use LogicException;
use stdClass;

/**
 * Типобезопасное чтение строки Query Builder. Мапперы и read-модели получают
 * значения нужного типа или понятную ошибку вместо mixed.
 */
final readonly class Row
{
    private function __construct(
        private stdClass $row,
        private string $source,
    ) {}

    public static function from(stdClass $row, string $source): self
    {
        return new self($row, $source);
    }

    public function string(string $column): string
    {
        $value = $this->row->{$column} ?? null;

        return is_string($value) ? $value : throw $this->unexpected($column, 'string');
    }

    public function nullableString(string $column): ?string
    {
        $value = $this->row->{$column} ?? null;

        if ($value === null || is_string($value)) {
            return $value;
        }

        throw $this->unexpected($column, 'string or null');
    }

    public function int(string $column): int
    {
        $value = $this->row->{$column} ?? null;

        return is_int($value) ? $value : throw $this->unexpected($column, 'int');
    }

    public function timestamp(string $column): DateTimeImmutable
    {
        return new DateTimeImmutable($this->string($column));
    }

    public function nullableTimestamp(string $column): ?DateTimeImmutable
    {
        $value = $this->nullableString($column);

        return $value === null ? null : new DateTimeImmutable($value);
    }

    /**
     * jsonb приезжает строкой, поэтому декодируем здесь же.
     *
     * @return array<string, mixed>
     */
    public function jsonObject(string $column): array
    {
        try {
            $decoded = json_decode($this->string($column), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw $this->unexpected($column, 'valid JSON');
        }

        // Пустой объект {} декодируется в [], и array_is_list() для него истинно,
        // поэтому список отсекаем только у непустых массивов
        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw $this->unexpected($column, 'JSON object');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function unexpected(string $column, string $expected): LogicException
    {
        return new LogicException(sprintf('Expected %s.%s to be %s', $this->source, $column, $expected));
    }
}
