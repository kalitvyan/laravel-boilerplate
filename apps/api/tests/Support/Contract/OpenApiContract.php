<?php

declare(strict_types=1);

namespace Tests\Support\Contract;

use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ServerRequestValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\AssertionFailedError;
use RuntimeException;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class OpenApiContract
{
    private static ?ServerRequestValidator $requestValidator = null;

    private static ?ResponseValidator $responseValidator = null;

    private static ?PsrHttpFactory $psrFactory = null;

    public static function assertMatches(Request $request, Response $response): void
    {
        self::boot();

        try {
            $operation = self::$requestValidator->validate(self::$psrFactory->createRequest($request));
            self::$responseValidator->validate($operation, self::$psrFactory->createResponse($response));
        } catch (Throwable $e) {
            if (! str_starts_with($e::class, 'League\\OpenAPIValidation\\')) {
                throw $e;
            }

            throw new AssertionFailedError(sprintf(
                "OpenAPI contract violation for %s %s (HTTP %d):\n%s",
                $request->getMethod(),
                $request->getPathInfo(),
                $response->getStatusCode(),
                self::describe($e),
            ), $e->getCode(), $e);
        }
    }

    /**
     * @phpstan-assert !null self::$requestValidator
     * @phpstan-assert !null self::$responseValidator
     * @phpstan-assert !null self::$psrFactory
     */
    private static function boot(): void
    {
        if (self::$requestValidator instanceof ServerRequestValidator && self::$responseValidator instanceof ResponseValidator && self::$psrFactory instanceof PsrHttpFactory) {
            return;
        }

        $builder = new ValidatorBuilder()->fromYamlFile(self::specPath());

        self::$requestValidator = $builder->getServerRequestValidator();
        self::$responseValidator = $builder->getResponseValidator();

        $factory = new Psr17Factory;
        self::$psrFactory = new PsrHttpFactory($factory, $factory, $factory, $factory);
    }

    private static function specPath(): string
    {
        $fromEnv = getenv('OPENAPI_SPEC_PATH');

        $candidates = array_filter([
            is_string($fromEnv) && $fromEnv !== '' ? $fromEnv : null,
            // внутри контейнера docs/api смонтирован в /spec
            '/spec/dist/openapi.yaml',
            // запуск с хоста: apps/api/tests/Support/Contract → корень репозитория
            dirname(__DIR__, 5).'/docs/api/dist/openapi.yaml',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException(sprintf(
            "OpenAPI bundle not found. Run: make spec\nLooked in:\n  - %s",
            implode("\n  - ", $candidates),
        ));
    }

    private static function describe(Throwable $e): string
    {
        $lines = [];

        for ($current = $e; $current instanceof Throwable; $current = $current->getPrevious()) {
            $lines[] = '  - '.$current->getMessage();
        }

        return implode("\n", $lines);
    }
}
