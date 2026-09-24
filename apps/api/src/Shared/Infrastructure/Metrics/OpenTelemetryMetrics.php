<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Metrics;

use LaravelBoilerplate\Shared\Application\Metrics\MetricName;
use LaravelBoilerplate\Shared\Application\Metrics\Metrics;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;

final class OpenTelemetryMetrics implements Metrics
{
    /**
     * Единицы по UCUM: s — секунды, 1 — безразмерное.
     *
     * @var array<string, array{string, string}>
     */
    private const array DEFINITIONS = [
        MetricName::HTTP_SERVER_DURATION => ['s', 'Duration of inbound HTTP requests'],
        MetricName::MESSAGING_PROCESS_DURATION => ['s', 'Duration of integration event handling'],
        MetricName::COMMAND_DURATION => ['s', 'Duration of command handling'],
        MetricName::OUTBOX_LAG => ['s', 'Age of the oldest unpublished outbox message'],
        MetricName::OUTBOX_PENDING => ['1', 'Unpublished outbox messages'],
        MetricName::OUTBOX_DEAD_LETTERED => ['1', 'Outbox messages moved to dead letter'],
        MetricName::QUEUE_DEPTH => ['1', 'Jobs waiting in queue'],
        MetricName::MESSAGE_DUPLICATES => ['1', 'Redelivered messages skipped by inbox'],
    ];

    private readonly MeterInterface $meter;

    /** @var array<string, CounterInterface> */
    private array $counters = [];

    /** @var array<string, HistogramInterface> */
    private array $histograms = [];

    /** @var array<string, GaugeInterface> */
    private array $gauges = [];

    public function __construct(MeterProviderInterface $meterProvider)
    {
        $this->meter = $meterProvider->getMeter('laravel-boilerplate');
    }

    public function increment(string $name, array $attributes = [], int $by = 1): void
    {
        [$unit, $description] = self::DEFINITIONS[$name] ?? ['1', ''];

        $this->counters[$name] ??= $this->meter->createCounter($name, $unit, $description);
        $this->counters[$name]->add($by, $this->attributes($attributes));
    }

    public function record(string $name, float $value, array $attributes = []): void
    {
        [$unit, $description] = self::DEFINITIONS[$name] ?? ['1', ''];

        $this->histograms[$name] ??= $this->meter->createHistogram($name, $unit, $description);
        $this->histograms[$name]->record($value, $this->attributes($attributes));
    }

    public function gauge(string $name, float $value, array $attributes = []): void
    {
        [$unit, $description] = self::DEFINITIONS[$name] ?? ['1', ''];

        $this->gauges[$name] ??= $this->meter->createGauge($name, $unit, $description);
        $this->gauges[$name]->record($value, $this->attributes($attributes));
    }

    /**
     * SDK требует непустые строковые ключи атрибутов.
     *
     * @param  array<string, string|int|bool>  $attributes
     * @return array<non-empty-string, string|int|bool>
     */
    private function attributes(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $key => $value) {
            if ($key !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
