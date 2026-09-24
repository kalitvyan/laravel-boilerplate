<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use LaravelBoilerplate\Shared\Application\Metrics\MetricName;
use LaravelBoilerplate\Shared\Application\Metrics\Metrics;
use LaravelBoilerplate\Shared\Infrastructure\Telemetry\FlushTelemetry;
use OpenTelemetry\SemConv\Attributes\HttpAttributes;
use Symfony\Component\HttpFoundation\Response;

final readonly class RecordHttpMetrics
{
    public function __construct(
        private Metrics $metrics,
        private FlushTelemetry $flush,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('health/*')) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $status = 500;

        try {
            $response = $next($request);
            $status = $response->getStatusCode();

            return $response;
        } finally {
            $this->metrics->record(
                MetricName::HTTP_SERVER_DURATION,
                (hrtime(true) - $startedAt) / 1_000_000_000,
                [
                    HttpAttributes::HTTP_REQUEST_METHOD => $request->getMethod(),
                    // Шаблон роута, а не URL: иначе кардинальность неограниченна
                    'http.route' => $this->routeTemplate($request),
                    HttpAttributes::HTTP_RESPONSE_STATUS_CODE => $status,
                ],
            );
        }
    }

    /**
     * Вызывается ядром после отправки ответа: задержки для клиента нет.
     */
    public function terminate(): void
    {
        ($this->flush)();
    }

    private function routeTemplate(Request $request): string
    {
        $route = $request->route();

        // Для 404 роута нет, и подставлять путь нельзя — это открытая кардинальность
        return $route instanceof Route ? '/'.ltrim($route->uri(), '/') : 'unmatched';
    }
}
