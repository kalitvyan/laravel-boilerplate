<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use LaravelBoilerplate\Identity\Infrastructure\Persistence\UsersTable;
use Override;

/**
 * Persistence-модель. Бизнес-логики здесь нет, её заполняет и читает только UserMapper.
 *
 * @property string $id
 * @property string $email
 * @property string $password_hash
 * @property string $status
 * @property CarbonImmutable $registered_at
 */
#[DateFormat('Y-m-d H:i:s.uP')]
#[Unguarded]
#[Hidden(['password_hash'])]
#[WithoutIncrementing]
final class UserModel extends Authenticatable
{
    use HasApiTokens;

    #[Override]
    protected $table = UsersTable::NAME;

    #[Override]
    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'immutable_datetime',
        ];
    }
}
