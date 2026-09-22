<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Storage;

use DateTimeImmutable;

final readonly class PresignedUpload
{
    /**
     * @param  array<string, string>  $headers  заголовки, которые клиент обязан отправить вместе с PUT
     */
    public function __construct(
        public string $url,
        public array $headers,
        public DateTimeImmutable $expiresAt,
    ) {}
}
