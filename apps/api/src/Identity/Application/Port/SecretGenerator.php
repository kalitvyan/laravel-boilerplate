<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Port;

interface SecretGenerator
{
    /**
     * Криптостойкий секрет, безопасный для URL и заголовков.
     */
    public function generate(): string;
}
