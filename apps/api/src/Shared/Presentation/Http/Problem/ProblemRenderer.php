<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Problem;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LaravelBoilerplate\Shared\Presentation\Http\Middleware\AssignRequestId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final readonly class ProblemRenderer
{
    private const int JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public function __construct(
        private ProblemMap $map,
        private Repository $config,
    ) {}

    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        if ($e instanceof HttpResponseException) {
            return null; // ответ уже сформирован, отдаём как есть
        }

        return match (true) {
            $e instanceof ValidationException => $this->validation($e, $request),
            $e instanceof AuthenticationException => $this->respond($request, 401, 'unauthenticated', 'Unauthenticated'),
            default => $this->fromThrowable($e, $request),
        };
    }

    private function fromThrowable(Throwable $e, Request $request): JsonResponse
    {
        $definition = $this->map->find($e);

        if ($definition instanceof ProblemDefinition) {
            return $this->respond($request, $definition->status, $definition->code, $definition->title, $e->getMessage());
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return $this->respond(
                $request,
                $status,
                $this->codeForStatus($status),
                Response::$statusTexts[$status] ?? 'Error',
                headers: $e->getHeaders(),
            );
        }

        $debug = $this->config->get('app.debug') === true;

        return $this->respond(
            $request,
            500,
            'internal_error',
            'Internal Server Error',
            $debug ? $e->getMessage() : null,
            $debug ? ['exception' => $e::class] : [],
        );
    }

    private function validation(ValidationException $e, Request $request): JsonResponse
    {
        $errors = [];
        $messages = $e->validator->errors();

        foreach ($e->validator->failed() as $field => $rules) {
            if (! is_array($rules)) {
                continue;
            }

            $fieldMessages = array_values($messages->get((string) $field));

            foreach (array_keys($rules) as $index => $rule) {
                $message = $fieldMessages[$index] ?? null;

                $errors[] = [
                    'pointer' => self::jsonPointer((string) $field),
                    'code' => Str::snake(class_basename((string) $rule)),
                    'message' => is_string($message) ? $message : $e->getMessage(),
                ];
            }
        }

        return $this->respond($request, 422, 'validation_failed', 'Validation failed', extensions: ['errors' => $errors]);
    }

    /**
     * @param  array<string, mixed>  $extensions
     * @param  array<mixed>  $headers
     */
    private function respond(
        Request $request,
        int $status,
        string $code,
        string $title,
        ?string $detail = null,
        array $extensions = [],
        array $headers = [],
    ): JsonResponse {
        $body = array_filter(
            [
                'type' => $this->typeFor($code),
                'title' => $title,
                'status' => $status,
                'detail' => $detail,
                'instance' => '/'.ltrim($request->path(), '/'),
                'code' => $code,
                'traceId' => $request->attributes->get(AssignRequestId::ATTRIBUTE),
            ],
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        return new JsonResponse(
            [...$body, ...$extensions],
            $status,
            [...$headers, 'Content-Type' => 'application/problem+json'],
            self::JSON_FLAGS,
        );
    }

    private function typeFor(string $code): string
    {
        $base = $this->config->get('api.problem_type_base_url');

        if (! is_string($base) || $base === '') {
            return 'about:blank';
        }

        return rtrim($base, '/').'/'.str_replace(['.', '_'], ['/', '-'], $code);
    }

    private function jsonPointer(string $field): string
    {
        $segments = array_map(
            static fn (string $segment): string => str_replace(['~', '/'], ['~0', '~1'], $segment),
            explode('.', $field),
        );

        return '/'.implode('/', $segments);
    }

    private function codeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            409 => 'conflict',
            415 => 'unsupported_media_type',
            429 => 'too_many_requests',
            503 => 'service_unavailable',
            default => 'http_'.$status,
        };
    }
}
