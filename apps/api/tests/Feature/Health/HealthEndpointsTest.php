<?php

declare(strict_types=1);

it('reports liveness', function (): void {
    $this->getJson('/health/live')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson(['status' => 'ok']);
});

it('reports readiness with database check', function (): void {
    $this->getJson('/health/ready')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', 'ok')
        ->assertJsonPath('checks.redis_state', 'ok')
        ->assertJsonPath('checks.redis_cache', 'ok');
});
