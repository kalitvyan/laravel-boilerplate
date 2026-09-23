<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus;

use Attribute;

/**
 * Грубая проверка права перед выполнением команды. Проверки, зависящие
 * от состояния агрегата, остаются в домене.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class RequiresPermission
{
    public function __construct(public string $permission) {}
}
