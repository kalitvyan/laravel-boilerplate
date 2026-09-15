<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Problem;

final readonly class ProblemDefinition
{
    public function __construct(
        public int $status,
        public string $code,
        public string $title,
    ) {}
}
