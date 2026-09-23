<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Domain\Access;

/**
 * Актор запроса. Иммутабелен и передаётся в команды явным полем,
 * чтобы ни один сценарий не зависел от ambient-состояния контейнера.
 */
final readonly class Principal
{
    public const string SYSTEM_ID = 'system';

    /**
     * @param  list<string>  $roles
     * @param  list<string>  $permissions  уже пересечены с abilities токена
     */
    private function __construct(
        public string $id,
        public array $roles,
        public array $permissions,
        public bool $isSystem,
        public ?string $tokenId,
    ) {}

    /**
     * @param  list<string>  $roles
     * @param  list<string>  $permissions
     */
    public static function user(string $id, array $roles, array $permissions, ?string $tokenId = null): self
    {
        return new self($id, $roles, $permissions, false, $tokenId);
    }

    /**
     * Консоль, планировщик, консьюмеры очередей: действуют от имени системы.
     */
    public static function system(): self
    {
        return new self(self::SYSTEM_ID, [], [], true, null);
    }

    public function can(string $permission): bool
    {
        return $this->isSystem || in_array($permission, $this->permissions, true);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function is(string $userId): bool
    {
        return ! $this->isSystem && $this->id === $userId;
    }
}
