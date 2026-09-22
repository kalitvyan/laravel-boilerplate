<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use LaravelBoilerplate\Shared\Application\Event\MessageMetadata;
use LaravelBoilerplate\Shared\Infrastructure\Clock\SystemClock;
use LaravelBoilerplate\Shared\Infrastructure\Outbox\OutboxPublisher;
use Tests\Fixtures\Shared\Event\ThingHappened;
use Tests\Fixtures\Shared\Event\ThingHappenedTranslator;

it('refuses to publish outside a transaction', function (): void {
    $connection = Mockery::mock(ConnectionInterface::class);
    $connection->shouldReceive('transactionLevel')->andReturn(0);
    $connection->shouldNotReceive('table');

    $resolver = Mockery::mock(ConnectionResolverInterface::class);
    $resolver->shouldReceive('connection')->andReturn($connection);

    $metadata = new class implements MessageMetadata
    {
        public function current(): array
        {
            return [];
        }
    };

    $translator = new ThingHappenedTranslator;
    $event = $translator(new ThingHappened('0191f2a0-0000-7000-8000-000000000005'));

    $publisher = new OutboxPublisher($resolver, $metadata, new SystemClock);

    expect(fn () => $publisher->publish($event))
        ->toThrow(LogicException::class, 'inside a transaction');
});
