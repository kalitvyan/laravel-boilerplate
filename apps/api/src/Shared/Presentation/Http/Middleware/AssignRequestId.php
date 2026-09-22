<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Принимает X-Request-Id от BFF (только валидный UUID, чтобы не допустить инъекций в логи) или генерирует новый.
 * На этапе 7 traceId будет браться из OpenTelemetry-контекста.
 */
final readonly class AssignRequestId
{
    public const string HEADER = 'X-Request-Id';

    public const string ATTRIBUTE = 'request_id';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::HEADER);
        $requestId = $incoming !== null && Uuid::isValid($incoming) ? $incoming : Uuid::v7()->toRfc4122();

        $request->attributes->set(self::ATTRIBUTE, $requestId);

        Context::add('trace_id', $requestId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
