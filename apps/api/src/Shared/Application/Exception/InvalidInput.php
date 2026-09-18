<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Exception;

use RuntimeException;

/**
 * Некорректный ввод, не относящийся к бизнес-правилам: курсор, лимит, формат параметров.
 */
class InvalidInput extends RuntimeException {}
