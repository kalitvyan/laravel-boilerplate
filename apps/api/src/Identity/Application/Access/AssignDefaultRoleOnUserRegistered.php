<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Access;

use LaravelBoilerplate\Identity\Domain\Access\Role;
use LaravelBoilerplate\Identity\Domain\Access\UserRoleRepository;
use LaravelBoilerplate\Identity\Domain\User\Event\UserRegistered;
use LaravelBoilerplate\Shared\Application\Event\DomainEventListener;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;
use LogicException;
use Psr\Clock\ClockInterface;

final readonly class AssignDefaultRoleOnUserRegistered implements DomainEventListener
{
    public function __construct(
        private UserRoleRepository $roles,
        // Имя роли, а не Repository: Application не зависит от конфига фреймворка
        private string $defaultRole,
        private ClockInterface $clock,
    ) {}

    public function __invoke(DomainEvent $event): void
    {
        if (! $event instanceof UserRegistered) {
            throw new LogicException(sprintf('%s cannot handle %s', self::class, $event::class));
        }

        $this->roles->assign($event->userId, Role::fromString($this->defaultRole), $this->clock->now());
    }
}
