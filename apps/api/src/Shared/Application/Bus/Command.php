<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus;

/**
 * Команда меняет состояние и ничего не возвращает.
 * ID новых агрегатов генерируется до dispatch и передаётся в команду.
 */
interface Command {}
