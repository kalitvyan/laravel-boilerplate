<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Domain\Exception;

use DomainException;

/**
 * Нарушение бизнес-правила. Сообщение должно быть безопасно для показа клиенту.
 */
abstract class DomainError extends DomainException {}
