<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Assigns application roles and keeps the legacy `users.is_admin` flag in
 * sync so the retired Jetstream application can still read it.
 */
class RoleAssignmentService
{
    /**
     * Assign the given roles to the user, replacing any existing assignment.
     *
     * @param  Role|array<int, Role>  $roles
     */
    public function assign(User $user, Role|array $roles): User
    {
        $roles = $roles instanceof Role ? [$roles] : $roles;

        $user->syncRoles(array_map(
            fn (Role $role): string => $this->ensureRole($role)->name,
            $roles,
        ));

        $this->syncLegacyAdminFlag($user);

        return $user;
    }

    /**
     * Assign the administrator role to the user.
     */
    public function assignAdmin(User $user): User
    {
        return $this->assign($user, Role::Admin);
    }

    /**
     * Make sure the role and its default permissions exist.
     */
    public function ensureRole(Role $role): RoleContract
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $model = RoleModel::findOrCreate($role->value, 'web');

        $model->syncPermissions($role->defaultPermissions());

        return $model;
    }

    /**
     * Mirror the administrator role onto the legacy `is_admin` flag.
     */
    public function syncLegacyAdminFlag(User $user): void
    {
        $isAdmin = $user->hasRole(Role::Admin);

        if ((bool) $user->is_admin === $isAdmin) {
            return;
        }

        $user->forceFill(['is_admin' => $isAdmin])->saveQuietly();
    }
}
