<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('outbox:prune')->daily()->onOneServer()->withoutOverlapping();

Schedule::command('identity:prune-refresh-tokens')->daily()->onOneServer()->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->daily()->onOneServer()->withoutOverlapping();
