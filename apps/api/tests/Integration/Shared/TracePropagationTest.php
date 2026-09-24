<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelBoilerplate\Identity\Application\RegisterUser\RegisterUser;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Shared\Application\Bus\CommandBus;
use LaravelBoilerplate\Shared\Application\Tracing\Tracer;
use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

beforeEach(function (): void {
    $this->storage = new ArrayObject;

    $this->app->instance(
        TracerProviderInterface::class,
        TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor(new InMemoryExporter($this->storage)))
            ->build(),
    );

    // Tracer уже мог быть разрешён с noop-провайдером
    $this->app->forgetInstance(Tracer::class);
});

it('stores trace context in outbox metadata', function (): void {
    $this->app->make(CommandBus::class)->dispatch(
        new RegisterUser(UserId::generate()->toString(), 'trace@example.com', 'correct horse battery staple'),
    );

    $row = DB::table('outbox_messages')->where('event_name', 'identity.user_registered')->sole();
    $metadata = json_decode((string) $row->metadata, true);

    expect($metadata)->toHaveKey('traceparent')
        // W3C: version-traceid-spanid-flags
        ->and($metadata['traceparent'])->toMatch('/^00-[0-9a-f]{32}-[0-9a-f]{16}-[0-9a-f]{2}$/');
});

it('links the outbox message to the command span', function (): void {
    $this->app->make(CommandBus::class)->dispatch(
        new RegisterUser(UserId::generate()->toString(), 'trace@example.com', 'correct horse battery staple'),
    );

    $spans = iterator_to_array($this->storage);
    $commandSpan = array_values(array_filter(
        $spans,
        static fn (object $span): bool => $span->getName() === 'RegisterUser',
    ));

    expect($commandSpan)->toHaveCount(1);

    $metadata = json_decode(
        (string) DB::table('outbox_messages')->where('event_name', 'identity.user_registered')->value('metadata'),
        true,
    );

    // Сообщение принадлежит трейсу команды: консьюмер продолжит ту же цепочку
    expect($metadata['traceparent'])->toContain($commandSpan[0]->getTraceId());
});

it('omits traceparent when tracing is disabled', function (): void {
    $this->app->instance(TracerProviderInterface::class, new NoopTracerProvider);
    $this->app->forgetInstance(Tracer::class);

    $this->app->make(CommandBus::class)->dispatch(
        new RegisterUser(UserId::generate()->toString(), 'trace@example.com', 'correct horse battery staple'),
    );

    $metadata = json_decode(
        (string) DB::table('outbox_messages')->where('event_name', 'identity.user_registered')->value('metadata'),
        true,
    );

    expect($metadata)->not->toHaveKey('traceparent');
});
