<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Health;

final readonly class ReadinessProbe
{
    /**
     * @param  list<HealthCheck>  $checks
     */
    public function __construct(private array $checks) {}

    /**
     * @return array<string, bool>
     */
    public function run(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            $results[$check->name()] = $check->check()->healthy;
        }

        return $results;
    }
}
