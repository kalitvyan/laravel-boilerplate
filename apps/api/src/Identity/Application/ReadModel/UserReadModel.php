<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\ReadModel;

/**
 * Read side: читает хранилище напрямую в DTO, минуя агрегат.
 */
interface UserReadModel
{
    public function find(string $userId): ?UserView;
}
