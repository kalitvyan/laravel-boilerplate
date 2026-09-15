<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use Tests\Fixtures\Shared\Bus\RecordTransactionLevel;
use Tests\Fixtures\Shared\Bus\RecordTransactionLevelHandler;

beforeEach(function (): void {
    RecordTransactionLevelHandler::$level = null;

    $this->app->extend(CommandHandlerMap::class, static fn (CommandHandlerMap $map): CommandHandlerMap => $map->with([
        RecordTransactionLevel::class => RecordTransactionLevelHandler::class,
    ]));
});

it('runs handler inside a transaction', function (): void {
    $this->app->make(CommandBus::class)->dispatch(new RecordTransactionLevel);

    expect(RecordTransactionLevelHandler::$level)->toBe(1)
        ->and(DB::transactionLevel())->toBe(0);
});

it('rolls back and rethrows on failure', function (): void {
    expect(fn () => $this->app->make(CommandBus::class)->dispatch(new RecordTransactionLevel(fail: true)))
        ->toThrow(RuntimeException::class, 'boom');

    expect(DB::transactionLevel())->toBe(0);
});

it('fails loudly when handler is missing', function (): void {
    $this->app->forgetInstance(CommandHandlerMap::class);
    $this->app->singleton(CommandHandlerMap::class, static fn (): CommandHandlerMap => new CommandHandlerMap);
    $this->app->forgetScopedInstances();

    expect(fn () => $this->app->make(CommandBus::class)->dispatch(new RecordTransactionLevel))
        ->toThrow(LogicException::class, 'No handler registered');
});
