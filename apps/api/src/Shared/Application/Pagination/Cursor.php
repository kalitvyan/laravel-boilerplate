<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Pagination;

use JsonException;

final readonly class Cursor
{
    private const int MAX_LENGTH = 1024;

    /**
     * @param  non-empty-array<string, string|int|float>  $position
     */
    private function __construct(public array $position) {}

    /**
     * @param  non-empty-array<string, string|int|float>  $position
     */
    public static function fromPosition(array $position): self
    {
        return new self($position);
    }

    public static function decode(string $encoded): self
    {
        if (strlen($encoded) > self::MAX_LENGTH || preg_match('/^[A-Za-z0-9_-]+$/', $encoded) !== 1) {
            throw InvalidCursor::malformed();
        }

        $json = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($json === false) {
            throw InvalidCursor::malformed();
        }

        try {
            $data = json_decode($json, true, 3, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidCursor::malformed();
        }

        if (! is_array($data) || $data === []) {
            throw InvalidCursor::malformed();
        }

        $position = [];

        foreach ($data as $key => $value) {
            if (! is_string($key) || (! is_string($value) && ! is_int($value) && ! is_float($value))) {
                throw InvalidCursor::malformed();
            }

            $position[$key] = $value;
        }

        return new self($position);
    }

    public function encode(): string
    {
        return rtrim(strtr(base64_encode(json_encode($this->position, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
}
