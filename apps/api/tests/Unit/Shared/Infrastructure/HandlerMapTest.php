<?php

declare(strict_types=1);

use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use Tests\Fixtures\Shared\Bus\RecordTransactionLevel;
use Tests\Fixtures\Shared\Bus\RecordTransactionLevelHandler;

it('is immutable', function (): void {
    $empty = new CommandHandlerMap;
    $filled = $empty->with([RecordTransactionLevel::class => RecordTransactionLevelHandler::class]);

    expect($empty->all())->toBe([])
        ->and($filled->handlerFor(new RecordTransactionLevel))->toBe(RecordTransactionLevelHandler::class);
});

it('rejects duplicate registration', function (): void {
    new CommandHandlerMap()
        ->with([RecordTransactionLevel::class => RecordTransactionLevelHandler::class])
        ->with([RecordTransactionLevel::class => RecordTransactionLevelHandler::class]);
})->throws(LogicException::class, 'already registered');
