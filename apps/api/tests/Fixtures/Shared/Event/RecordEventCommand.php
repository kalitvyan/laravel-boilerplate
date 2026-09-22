<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Event;

use LaravelBoilerplate\Shared\Application\Bus\Command;

final readonly class RecordEventCommand implements Command
{
    public function __construct(
        public string $thingId,
        public bool $fail = false,
    ) {}
}
