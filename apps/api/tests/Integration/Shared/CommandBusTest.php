<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\AuthorizeCommandMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\MeasureCommandMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\PublishRecordedEventsMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\TraceCommandMiddleware;
use LaravelBoilerplate\Shared\Application\Bus\Middleware\TransactionalMiddleware;
use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Bus\ContainerCommandBus;
use ReflectionProperty;
use Tests\Fixtures\Shared\Bus\RecordTransactionLevel;
use Tests\Fixtures\Shared\Bus\RecordTransactionLevelHandler;

beforeEach(function (): void {
    RecordTransactionLevelHandler::$level = null;

    $this->app->extend(CommandHandlerMap::class, static fn (CommandHandlerMap $map): CommandHandlerMap => $map->with([
        RecordTransactionLevel::class => RecordTransactionLevelHandler::class,
    ]));
});

it('runs handler inside a transaction', function (): void {
    $before = DB::transactionLevel();

    $this->app->make(CommandBus::class)->dispatch(new RecordTransactionLevel);

    expect(RecordTransactionLevelHandler::$level)->toBe($before + 1)
        ->and(DB::transactionLevel())->toBe($before);
});

it('rolls back and rethrows on failure', function (): void {
    $before = DB::transactionLevel();

    expect(fn () => $this->app->make(CommandBus::class)->dispatch(new RecordTransactionLevel(fail: true)))
        ->toThrow(RuntimeException::class, 'boom');

    expect(DB::transactionLevel())->toBe($before);
});

it('fails loudly when handler is missing', function (): void {
    $bus = new ContainerCommandBus($this->app, new CommandHandlerMap);

    expect(fn () => $bus->dispatch(new RecordTransactionLevel))
        ->toThrow(LogicException::class, 'No handler registered');
});

it('assembles the middleware pipeline in order', function (): void {
    $bus = $this->app->make(CommandBus::class);

    $middleware = new ReflectionProperty($bus, 'middleware')->getValue($bus);

    expect($middleware)->toBeArray();

    $classes = array_map(static fn (object $item): string => $item::class, $middleware);

    // Порядок — архитектурное решение, а не деталь:
    // Authorize до транзакции, чтобы отказ её не открывал;
    // Trace и Measure снаружи, чтобы видеть время удержания блокировок;
    // PublishRecordedEvents внутри, чтобы outbox коммитился вместе с агрегатом
    expect($classes)->toBe([
        AuthorizeCommandMiddleware::class,
        TraceCommandMiddleware::class,
        MeasureCommandMiddleware::class,
        TransactionalMiddleware::class,
        PublishRecordedEventsMiddleware::class,
    ]);
});
