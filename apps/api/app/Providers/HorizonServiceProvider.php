<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Ops-UI, не бизнес-авторизация. В local Horizon пускает без Gate.
     * В остальных окружениях доступ включается явно и дополнительно закрывается на уровне сети/ingress.
     */
    protected function gate(): void
    {
        Gate::define(
            ability: 'viewHorizon',
            callback: static fn (?Authenticatable $user = null): bool => config()->boolean('horizon.dashboard_enabled'),
        );
    }
}
