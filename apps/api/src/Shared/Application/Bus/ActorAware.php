<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Bus;

use LaravelBoilerplate\Shared\Domain\Access\Principal;

/**
 * Команда, выполняемая от имени актора.
 */
interface ActorAware
{
    public function actor(): Principal;
}
