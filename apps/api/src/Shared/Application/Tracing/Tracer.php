<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Tracing;

interface Tracer
{
    /**
     * Выполняет $callback внутри спана. Исключение помечает спан как ошибочный и пробрасывается дальше.
     *
     * @template T
     *
     * @param  array<string, scalar>  $attributes
     * @param  callable(): T  $callback
     * @return T
     */
    public function span(string $name, array $attributes, callable $callback): mixed;

    /**
     * W3C traceparent текущего контекста для передачи в сообщения и джобы.
     */
    public function currentTraceparent(): ?string;

    public function currentTraceId(): ?string;
}
