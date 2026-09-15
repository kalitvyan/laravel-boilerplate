<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Bus;

use LaravelBoilerplate\Shared\Application\Bus\Command;

final readonly class RecordTransactionLevel implements Command
{
    public function __construct(public bool $fail = false) {}
}
