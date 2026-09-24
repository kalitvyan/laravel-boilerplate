<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Metrics;

/**
 * Имена метрик — такой же контракт, как поля API: на них завязаны дашборды и алерты.
 */
final readonly class MetricName
{
    // Семантические конвенции OTel
    public const string HTTP_SERVER_DURATION = 'http.server.request.duration';

    public const string MESSAGING_PROCESS_DURATION = 'messaging.process.duration';

    // Прикладные
    public const string COMMAND_DURATION = 'app.command.duration';

    public const string OUTBOX_LAG = 'app.outbox.lag';

    public const string OUTBOX_PENDING = 'app.outbox.pending';

    public const string OUTBOX_DEAD_LETTERED = 'app.outbox.dead_lettered';

    public const string QUEUE_DEPTH = 'app.queue.depth';

    public const string MESSAGE_DUPLICATES = 'app.messaging.duplicates';
}
