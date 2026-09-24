<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SemConv\Attributes\ClientAttributes;
use OpenTelemetry\SemConv\Attributes\HttpAttributes;
use OpenTelemetry\SemConv\Attributes\ServerAttributes;
use OpenTelemetry\SemConv\Attributes\UrlAttributes;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Корневой спан запроса. Родитель берётся из traceparent, присланного BFF,
 * поэтому трейс сквозной от браузера до консьюмера очереди.
 */
final readonly class TraceRequest
{
    public function __construct(
        private TracerProviderInterface $tracerProvider,
        private bool $enabled,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->enabled || $request->is('health/*')) {
            return $next($request);
        }

        $parent = TraceContextPropagator::getInstance()->extract($request->headers->all());

        // Имя спана — шаблон роута, а не URL: иначе кардинальность метрик взрывается
        $route = $request->route();
        $template = $route instanceof Route ? '/'.ltrim($route->uri(), '/') : $request->getPathInfo();
        $name = sprintf('%s %s', $request->getMethod(), $template);

        $span = $this->tracerProvider->getTracer('laravel-boilerplate')
            ->spanBuilder($name)
            ->setParent($parent)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setAttributes([
                HttpAttributes::HTTP_REQUEST_METHOD => $request->getMethod(),
                UrlAttributes::URL_PATH => $request->getPathInfo(),
                ServerAttributes::SERVER_ADDRESS => $request->getHost(),
                ClientAttributes::CLIENT_ADDRESS => $request->ip(),
            ])
            ->startSpan();

        $scope = $span->activate();

        try {
            $response = $next($request);
            $span->setAttribute(HttpAttributes::HTTP_RESPONSE_STATUS_CODE, $response->getStatusCode());

            if ($response->getStatusCode() >= 500) {
                $span->setStatus(StatusCode::STATUS_ERROR);
            }

            return $response;
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());

            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
