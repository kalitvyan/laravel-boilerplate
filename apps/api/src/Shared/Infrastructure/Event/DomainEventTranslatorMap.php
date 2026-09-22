<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Event;

use LaravelBoilerplate\Shared\Infrastructure\Bus\HandlerMap;

/**
 * Один транслятор на доменное событие: событие либо публикуется наружу, либо нет.
 */
final readonly class DomainEventTranslatorMap extends HandlerMap {}
