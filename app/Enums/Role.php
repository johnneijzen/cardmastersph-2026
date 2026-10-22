<?php

namespace App\Enums;

/**
 * Application roles.
 *
 * The role names are persisted in the `roles` table and are compared
 * literally, so changing a case here is a breaking change for existing data.
 */
enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';

    /**
     * The role that is granted every permission.
     */
    public static function superAdmin(): self
    {
        return self::Admin;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::Staff => 'Staff',
        };
    }

    /**
     * The permissions the role grants by default.
     *
     * @return array<int, string>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => Permission::values(),
            self::Manager => array_map(
                fn (Permission $permission): string => $permission->value,
                Permission::managerDefaults(),
            ),
            self::Staff => array_map(
                fn (Permission $permission): string => $permission->value,
                Permission::staffDefaults(),
            ),
        };
    }
}
