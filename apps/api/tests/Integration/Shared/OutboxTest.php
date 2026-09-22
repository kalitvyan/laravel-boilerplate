<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Event\DomainEventListenerMap;
use LaravelBoilerplate\Shared\Infrastructure\Event\DomainEventTranslatorMap;
use Tests\Fixtures\Shared\Event\CascadingListener;
use Tests\Fixtures\Shared\Event\RecordEventCommand;
use Tests\Fixtures\Shared\Event\RecordEventHandler;
use Tests\Fixtures\Shared\Event\ThingHappened;
use Tests\Fixtures\Shared\Event\ThingHappenedTranslator;

beforeEach(function (): void {
    CascadingListener::$calls = 0;

    $this->app->extend(CommandHandlerMap::class, static fn (CommandHandlerMap $map): CommandHandlerMap => $map->with([
        RecordEventCommand::class => RecordEventHandler::class,
    ]));
});

it('writes an outbox row in the same transaction as the command', function (): void {
    $this->app->extend(DomainEventTranslatorMap::class, static fn (DomainEventTranslatorMap $map): DomainEventTranslatorMap => $map->with([
        ThingHappened::class => ThingHappenedTranslator::class,
    ]));

    Context::add('trace_id', '0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d');

    $this->app->make(CommandBus::class)->dispatch(new RecordEventCommand('0191f2a0-0000-7000-8000-000000000001'));

    $row = DB::table('outbox_messages')->sole();

    expect($row->event_name)->toBe('testing.thing_happened')
        ->and($row->event_version)->toBe(1)
        ->and($row->aggregate_id)->toBe('0191f2a0-0000-7000-8000-000000000001')
        ->and($row->published_at)->toBeNull()
        ->and(json_decode((string) $row->payload, true))->toBe(['thingId' => '0191f2a0-0000-7000-8000-000000000001'])
        ->and(json_decode((string) $row->metadata, true))->toBe(['trace_id' => '0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d']);
});

it('writes nothing when the command fails', function (): void {
    $this->app->extend(DomainEventTranslatorMap::class, static fn (DomainEventTranslatorMap $map): DomainEventTranslatorMap => $map->with([
        ThingHappened::class => ThingHappenedTranslator::class,
    ]));

    expect(fn () => $this->app->make(CommandBus::class)->dispatch(
        new RecordEventCommand('0191f2a0-0000-7000-8000-000000000002', fail: true),
    ))->toThrow(RuntimeException::class);

    expect(DB::table('outbox_messages')->count())->toBe(0);
});

it('does not publish events without a registered translator', function (): void {
    $this->app->make(CommandBus::class)->dispatch(new RecordEventCommand('0191f2a0-0000-7000-8000-000000000003'));

    expect(DB::table('outbox_messages')->count())->toBe(0);
});

it('runs domain listeners and drains cascaded events', function (): void {
    $this->app->extend(DomainEventListenerMap::class, static fn (DomainEventListenerMap $map): DomainEventListenerMap => $map->with([
        ThingHappened::class => [CascadingListener::class],
    ]));

    $this->app->make(CommandBus::class)->dispatch(new RecordEventCommand('0191f2a0-0000-7000-8000-000000000004'));

    // Листенер вызван один раз; второе событие обработано следующей волной без зацикливания
    expect(CascadingListener::$calls)->toBe(1);
});
