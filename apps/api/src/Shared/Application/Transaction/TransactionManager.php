<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Transaction;

interface TransactionManager
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function transactional(callable $callback): mixed;
}
