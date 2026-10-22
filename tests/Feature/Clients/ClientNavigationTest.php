<?php

use App\Enums\Permission;
use App\Models\User;
use App\Support\Navigation;

test('the client entries of the navigation are shown to a staff member', function () {
    $staff = User::factory()->staff()->create();

    $routes = collect(Navigation::groups())
        ->flatMap(fn ($group) => $group->items)
        ->filter(fn ($item) => $item->isVisibleTo($staff))
        ->map(fn ($item) => $item->route)
        ->all();

    expect($routes)->toContain('clients.index', 'client-contact-persons.index', 'client-agents.index');
});

test('the client entries of the navigation are hidden from a user without a role', function () {
    $user = User::factory()->create();

    $routes = collect(Navigation::groups())
        ->flatMap(fn ($group) => $group->items)
        ->filter(fn ($item) => $item->isVisibleTo($user))
        ->map(fn ($item) => $item->route)
        ->all();

    expect($routes)->not->toContain('clients.index')
        ->and($routes)->not->toContain('client-contact-persons.index')
        ->and($routes)->not->toContain('client-agents.index');
});

test('the client entries of the navigation follow the permissions of the roles', function () {
    $staff = User::factory()->staff()->create();
    $manager = User::factory()->manager()->create();

    expect($staff->can(Permission::ViewClients->value))->toBeTrue()
        ->and($staff->can(Permission::ExportClients->value))->toBeFalse()
        ->and($manager->can(Permission::ExportClients->value))->toBeTrue();
});
