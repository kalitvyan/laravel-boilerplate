<?php

declare(strict_types=1);

use LaravelBoilerplate\Identity\Contract\Event\UserRegisteredV1;
use LaravelBoilerplate\Shared\Application\Bus\ActorAware;
use LaravelBoilerplate\Shared\Application\Bus\RequiresPermission;
use LaravelBoilerplate\Shared\Infrastructure\Event\IntegrationEventSubscriberMap;
use Symfony\Component\Finder\Finder;

/**
 * @return list<ReflectionClass<object>>
 */
$commandsWithPermission = static function (): array {
    $classes = [];

    foreach (Finder::create()->files()->in(base_path('src'))->name('*.php') as $file) {
        $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
        $class = 'LaravelBoilerplate\\'.$relative;

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->getAttributes(RequiresPermission::class) !== []) {
            $classes[] = $reflection;
        }
    }

    return $classes;
};

it('finds commands to check', function () use ($commandsWithPermission): void {
    // Страховка: пустой список не должен выглядеть как успешная проверка
    expect($commandsWithPermission())->not->toBeEmpty();
});

it('requires an actor on every permission-guarded command', function () use ($commandsWithPermission): void {
    $offenders = array_map(
        static fn (ReflectionClass $class): string => $class->getName(),
        array_filter(
            $commandsWithPermission(),
            static fn (ReflectionClass $class): bool => ! $class->implementsInterface(ActorAware::class),
        ),
    );

    expect(array_values($offenders))->toBe([]);
});

it('references only declared permissions', function () use ($commandsWithPermission): void {
    $declared = config('identity.rbac.permissions');

    $unknown = [];

    foreach ($commandsWithPermission() as $class) {
        $permission = $class->getAttributes(RequiresPermission::class)[0]->newInstance()->permission;

        if (! in_array($permission, $declared, true)) {
            $unknown[] = sprintf('%s: %s', $class->getShortName(), $permission);
        }
    }

    expect($unknown)->toBe([]);
});

it('registers integration event subscribers at container build time', function (): void {
    $map = $this->app->make(IntegrationEventSubscriberMap::class);

    // extend() в boot() не успевает примениться: карта резолвится раньше
    expect($map->handlersFor(UserRegisteredV1::NAME, 1))->not->toBeEmpty();
});
