<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Metrics;

interface Metrics
{
    /**
     * @param  array<string, string|int|bool>  $attributes  только значения с низкой кардинальностью
     */
    public function increment(string $name, array $attributes = [], int $by = 1): void;

    /**
     * Наблюдение для гистограммы: длительность в секундах.
     *
     * @param  array<string, string|int|bool>  $attributes
     */
    public function record(string $name, float $value, array $attributes = []): void;

    /**
     * Мгновенное значение.
     *
     * @param  array<string, string|int|bool>  $attributes
     */
    public function gauge(string $name, float $value, array $attributes = []): void;
}
