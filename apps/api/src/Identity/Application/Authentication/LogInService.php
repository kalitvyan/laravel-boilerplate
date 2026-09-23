<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use LaravelBoilerplate\Identity\Application\Port\PasswordHasher;
use LaravelBoilerplate\Identity\Domain\Token\TokenFamilyId;
use LaravelBoilerplate\Identity\Domain\User\Email;
use LaravelBoilerplate\Identity\Domain\User\HashedPassword;
use LaravelBoilerplate\Identity\Domain\User\InvalidEmail;
use LaravelBoilerplate\Identity\Domain\User\PlainPassword;
use LaravelBoilerplate\Identity\Domain\User\User;
use LaravelBoilerplate\Identity\Domain\User\UserRepository;
use LaravelBoilerplate\Identity\Domain\User\WeakPassword;
use LaravelBoilerplate\Shared\Application\Transaction\TransactionManager;
use Psr\Clock\ClockInterface;

final readonly class LogInService
{
    /**
     * bcrypt-хеш строки, которая никогда не является валидным паролем. Нужен, чтобы
     * для несуществующего email тратилось столько же времени, сколько для существующего.
     */
    private const string DUMMY_HASH = '$2y$12$KIXQb8kVQ0WqZ8z1hQ0iEeC4Rk5N0oQwqzGZ0kM3pX9d0m0YV5Vhe';

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwords,
        private SessionIssuer $sessions,
        private TransactionManager $transactions,
        private ClockInterface $clock,
    ) {}

    public function __invoke(LogIn $input): SessionTokens
    {
        return $this->transactions->transactional(function () use ($input): SessionTokens {
            try {
                $email = Email::fromString($input->email);
                $password = PlainPassword::fromString($input->password);
            } catch (InvalidEmail|WeakPassword) {
                // Форма ввода не должна раскрывать политику паролей уже существующих аккаунтов
                throw InvalidCredentials::create();
            }

            $user = $this->users->findByEmail($email);

            if (! $user instanceof User) {
                $this->passwords->verify($password, HashedPassword::fromHash(self::DUMMY_HASH));

                throw InvalidCredentials::create();
            }

            if (! $this->passwords->verify($password, $user->password())) {
                throw InvalidCredentials::create();
            }

            if ($user->isBlocked()) {
                throw UserIsBlocked::create();
            }

            // Параметры хеширования могли ужесточиться со времени регистрации
            if ($this->passwords->needsRehash($user->password())) {
                $user->changePassword($this->passwords->hash($password), $this->clock->now());
                $this->users->save($user);
            }

            return $this->sessions->issue($user->id(), TokenFamilyId::generate());
        });
    }
}
