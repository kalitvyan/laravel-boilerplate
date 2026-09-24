<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Telemetry;

use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Trace\TracerProvider;

/**
 * В PHP нет фонового потока экспорта. BatchSpanProcessor умеет отправлять спаны сам
 * по таймеру, а метрики уезжают только по forceFlush, поэтому его вызывает middleware
 * после ответа клиенту — не чаще, чем раз в $minInterval секунд.
 */
final class FlushTelemetry
{
    private float $lastFlushAt = 0.0;

    public function __construct(
        private readonly TracerProviderInterface $tracerProvider,
        private readonly MeterProviderInterface $meterProvider,
        private readonly float $minInterval = 10.0,
    ) {}

    public function __invoke(): void
    {
        $now = microtime(true);

        if ($now - $this->lastFlushAt < $this->minInterval) {
            return;
        }

        $this->lastFlushAt = $now;
        $this->flush();
    }

    /**
     * Для короткоживущих процессов: команда завершится раньше любого интервала.
     */
    public function force(): void
    {
        $this->lastFlushAt = microtime(true);
        $this->flush();
    }

    private function flush(): void
    {
        if ($this->tracerProvider instanceof TracerProvider) {
            $this->tracerProvider->forceFlush();
        }

        if ($this->meterProvider instanceof MeterProvider) {
            $this->meterProvider->forceFlush();
        }
    }
}
