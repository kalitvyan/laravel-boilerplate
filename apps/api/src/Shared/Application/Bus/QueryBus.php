<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus;

interface QueryBus
{
    /**
     * @template TResult
     *
     * @param  Query<TResult>  $query
     * @return TResult
     */
    public function ask(Query $query): mixed;
}
