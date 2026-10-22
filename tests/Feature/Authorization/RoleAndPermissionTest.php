<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Database\Seeders\RolesAndPermissionsSeeder;

test('the administrator role is granted every ability', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->can(Permission::ViewClients->value))->toBeTrue()
        ->and($admin->can(Permission::ExecuteAdminTools->value))->toBeTrue()
        ->and($admin->can(Permission::ViewActivityLog->value))->toBeTrue();
});

test('the staff role is limited to the daily operations', function () {
    $staff = User::factory()->staff()->create();

    expect($staff->isAdmin())->toBeFalse()
        ->and($staff->can(Permission::ViewClients->value))->toBeTrue()
        ->and($staff->can(Permission::CreateJobOrders->value))->toBeTrue()
        ->and($staff->can(Permission::DeleteClients->value))->toBeFalse()
        ->and($staff->can(Permission::ExecuteAdminTools->value))->toBeFalse()
        ->and($staff->can(Permission::ViewActivityLog->value))->toBeFalse();
});

test('the manager role may archive job orders but may not use the admin tools', function () {
    $manager = User::factory()->manager()->create();

    expect($manager->can(Permission::ArchiveJobOrders->value))->toBeTrue()
        ->and($manager->can(Permission::ViewAccountsReceivable->value))->toBeTrue()
        ->and($manager->can(Permission::ExecuteAdminTools->value))->toBeFalse();
});

test('a user without a role is refused every permission', function () {
    $user = User::factory()->create();

    expect($user->can(Permission::ViewClients->value))->toBeFalse()
        ->and($user->isAdmin())->toBeFalse();
});

test('the legacy admin flag is mirrored onto the administrator role', function () {
    $user = User::factory()->create(['is_admin' => true]);

    app(RoleAssignmentService::class)->assign($user, Role::Admin);

    expect($user->fresh()->isAdmin())->toBeTrue();
});

test('roles and permissions are seeded for the application', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Spatie\Permission\Models\Role::pluck('name')->all())
        ->toContain(Role::Admin->value, Role::Manager->value, Role::Staff->value);

    expect(Spatie\Permission\Models\Permission::count())
        ->toBe(count(Permission::cases()));
});
