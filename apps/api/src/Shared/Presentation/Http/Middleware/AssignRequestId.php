<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use LaravelBoilerplate\Shared\Application\Tracing\Tracer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Идентификатор запроса: trace id из OTel, иначе присланный BFF валидный UUID, иначе свой.
 */
final readonly class AssignRequestId
{
    public const string HEADER = 'X-Request-Id';

    public const string ATTRIBUTE = 'request_id';

    public function __construct(private Tracer $tracer) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::HEADER);

        $requestId = $this->tracer->currentTraceId()
            ?? ($incoming !== null && Uuid::isValid($incoming) ? $incoming : Uuid::v7()->toRfc4122());

        $request->attributes->set(self::ATTRIBUTE, $requestId);
        Context::add('trace_id', $requestId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
