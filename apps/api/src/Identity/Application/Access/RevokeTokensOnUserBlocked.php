<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Access;

use LaravelBoilerplate\Identity\Application\Port\AccessTokenRevoker;
use LaravelBoilerplate\Identity\Domain\Token\RefreshTokenRepository;
use LaravelBoilerplate\Identity\Domain\User\Event\UserBlocked;
use LaravelBoilerplate\Shared\Application\Event\DomainEventListener;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;
use LogicException;
use Psr\Clock\ClockInterface;

final readonly class RevokeTokensOnUserBlocked implements DomainEventListener
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private AccessTokenRevoker $accessTokens,
        private ClockInterface $clock,
    ) {}

    public function __invoke(DomainEvent $event): void
    {
        if (! $event instanceof UserBlocked) {
            throw new LogicException(sprintf('%s cannot handle %s', self::class, $event::class));
        }

        // Внутри транзакции команды: блокировка и отзыв сессий применяются атомарно
        $this->refreshTokens->revokeAllForUser($event->userId, $this->clock->now());
        $this->accessTokens->revokeAllForUser($event->userId);
    }
}
