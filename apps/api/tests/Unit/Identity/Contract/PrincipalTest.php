<?php

declare(strict_types=1);

use LaravelBoilerplate\Shared\Domain\Access\Principal;

it('answers by permission', function (): void {
    $principal = Principal::user('u-1', ['support'], ['identity.users.read']);

    expect($principal->can('identity.users.read'))->toBeTrue()
        ->and($principal->can('identity.users.block'))->toBeFalse()
        ->and($principal->hasRole('support'))->toBeTrue()
        ->and($principal->is('u-1'))->toBeTrue()
        ->and($principal->is('u-2'))->toBeFalse();
});

it('grants everything to the system actor', function (): void {
    $system = Principal::system();

    expect($system->can('anything.at.all'))->toBeTrue()
        ->and($system->isSystem)->toBeTrue()
        // Системный актор не совпадает ни с одним пользователем
        ->and($system->is(Principal::SYSTEM_ID))->toBeFalse();
});
