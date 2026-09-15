<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Health;

use Illuminate\Http\JsonResponse;
use LaravelBoilerplate\Shared\Application\Health\ReadinessProbe;

final readonly class ReadinessController
{
    public function __construct(private ReadinessProbe $probe) {}

    public function __invoke(): JsonResponse
    {
        $results = $this->probe->run();
        $healthy = ! in_array(false, $results, true);

        return new JsonResponse(
            data: [
                'status' => $healthy ? 'ok' : 'fail',
                'checks' => array_map(static fn (bool $ok): string => $ok ? 'ok' : 'fail', $results),
            ],
            status: $healthy ? JsonResponse::HTTP_OK : JsonResponse::HTTP_SERVICE_UNAVAILABLE,
            headers: ['Cache-Control' => 'no-store'],
        );
    }
}
