<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Persistence;

use DateTimeImmutable;

final readonly class Timestamp
{
    /**
     * Микросекунды и смещение обязательны: timestamp(6) with time zone в схеме.
     */
    public const string FORMAT = 'Y-m-d H:i:s.uP';

    public static function format(DateTimeImmutable $at): string
    {
        return $at->format(self::FORMAT);
    }
}
