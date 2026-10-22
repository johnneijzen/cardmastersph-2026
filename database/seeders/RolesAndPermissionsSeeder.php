<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Create the application roles, attach their permissions and backfill the
     * role of every user that does not have one yet.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = app(RoleAssignmentService::class);

        foreach (Role::cases() as $role) {
            $roles->ensureRole($role);
        }

        $this->backfillUserRoles($roles);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Give every user without a role a default one, mapping the legacy
     * `is_admin` flag onto the administrator role.
     */
    protected function backfillUserRoles(RoleAssignmentService $roles): void
    {
        User::query()
            ->whereDoesntHave('roles')
            ->eachById(function (User $user) use ($roles): void {
                $roles->assign($user, $user->is_admin ? Role::Admin : Role::Staff);
            });
    }
}
