<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Exception;

use RuntimeException;

/**
 * Базовый класс для "ресурс не найден".
 * Контексты наследуют: UserNotFound extends NotFound.
 */
class NotFound extends RuntimeException {}
