<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Attributes\Table;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

#[DateFormat('Y-m-d H:i:s.uP')]
#[Table(name: 'identity.personal_access_tokens')]
final class PersonalAccessToken extends SanctumPersonalAccessToken {}
