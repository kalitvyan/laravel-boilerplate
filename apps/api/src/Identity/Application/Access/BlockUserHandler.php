<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Access;

use LaravelBoilerplate\Identity\Application\UserNotFound;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Shared\Application\Exception\AccessDenied;
use Psr\Clock\ClockInterface;

final readonly class BlockUserHandler
{
    public function __construct(
        private UserRepository $users,
        private ClockInterface $clock,
    ) {}

    public function __invoke(BlockUser $command): void
    {
        // Право на блокировку есть (проверено middleware), но заблокировать себя нельзя:
        // это правило зависит от актора и цели, поэтому живёт в сценарии
        if ($command->actor()->is($command->userId)) {
            throw new AccessDenied('You cannot block yourself');
        }

        $user = $this->users->find(UserId::fromString($command->userId))
            ?? throw UserNotFound::withId($command->userId);

        $user->block($this->clock->now());

        $this->users->save($user);
    }
}
