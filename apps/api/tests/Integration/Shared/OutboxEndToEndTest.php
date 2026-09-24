<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Infrastructure\Bus\CommandHandlerMap;
use LaravelBoilerplate\Shared\Infrastructure\Event\DomainEventTranslatorMap;
use LaravelBoilerplate\Shared\Infrastructure\Event\IntegrationEventSubscriberMap;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\HandleIntegrationEvent;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxRelay;
use Tests\Fixtures\Shared\Event\RecordEventCommand;
use Tests\Fixtures\Shared\Event\RecordEventHandler;
use Tests\Fixtures\Shared\Event\RecordingIntegrationHandler;
use Tests\Fixtures\Shared\Event\ThingHappened;
use Tests\Fixtures\Shared\Event\ThingHappenedTranslator;

beforeEach(function (): void {
    RecordingIntegrationHandler::$received = [];
    Queue::fake();

    $this->app->extend(CommandHandlerMap::class, static fn (CommandHandlerMap $map): CommandHandlerMap => $map->with([
        RecordEventCommand::class => RecordEventHandler::class,
    ]));

    $this->app->extend(DomainEventTranslatorMap::class, static fn (DomainEventTranslatorMap $map): DomainEventTranslatorMap => $map->with([
        ThingHappened::class => ThingHappenedTranslator::class,
    ]));

    $this->app->extend(IntegrationEventSubscriberMap::class, static fn (IntegrationEventSubscriberMap $map): IntegrationEventSubscriberMap => $map
        ->with('testing.thing_happened', 1, [RecordingIntegrationHandler::class]));

    // Выполняет джобы, которые relay поставил в очередь, тем же путём, что и воркер
    $this->runQueuedJobs = function (): int {
        $pushed = Queue::pushedJobs()[HandleIntegrationEvent::class] ?? [];
        $count = 0;

        foreach ($pushed as $entry) {
            $job = $entry['job'] ?? null;

            if ($job instanceof HandleIntegrationEvent) {
                $this->app->call($job->handle(...));
                $count++;
            }
        }

        return $count;
    };
});

it('delivers a domain event to an integration handler through the outbox', function (): void {
    $this->app->make(CommandBus::class)->dispatch(
        new RecordEventCommand('0191f2a0-0000-7000-8000-000000000030'),
    );

    // Команда закоммичена: сообщение в outbox, но ещё не опубликовано
    $message = DB::table('outbox_messages')->sole();

    expect($message->published_at)->toBeNull()
        ->and(RecordingIntegrationHandler::$received)->toBe([]);

    $this->app->make(OutboxRelay::class)->relayBatch(10);

    expect(($this->runQueuedJobs)())->toBe(1)
        ->and(RecordingIntegrationHandler::$received)->toBe([$message->id])
        ->and(DB::table('outbox_messages')->where('id', $message->id)->value('published_at'))->not->toBeNull()
        ->and(DB::table('inbox_messages')->where('message_id', $message->id)->exists())->toBeTrue();
});

it('does not process a redelivered message twice', function (): void {
    $this->app->make(CommandBus::class)->dispatch(
        new RecordEventCommand('0191f2a0-0000-7000-8000-000000000031'),
    );

    $relay = $this->app->make(OutboxRelay::class);
    $relay->relayBatch(10);
    ($this->runQueuedJobs)();

    // Имитируем повторную доставку: сообщение снова публикуется и джоб выполняется ещё раз
    $message = DB::table('outbox_messages')->sole();
    DB::table('outbox_messages')->where('id', $message->id)->update(['published_at' => null]);
    $relay->relayBatch(10);
    ($this->runQueuedJobs)();

    // Inbox не дал обработать дважды
    expect(RecordingIntegrationHandler::$received)->toHaveCount(1)
        ->and(DB::table('inbox_messages')->count())->toBe(1);
});
