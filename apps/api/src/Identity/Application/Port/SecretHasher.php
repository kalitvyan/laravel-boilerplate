<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Port;

interface SecretHasher
{
    /**
     * Быстрый хеш для секретов высокой энтропии: перебор невозможен,
     * а искать по индексу нужно за один запрос.
     */
    public function hash(string $secret): string;
}
