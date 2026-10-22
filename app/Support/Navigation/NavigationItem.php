<?php

namespace App\Support\Navigation;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * A single entry in the application navigation.
 */
final readonly class NavigationItem
{
    public function __construct(
        public string $label,
        public string $route,
        public string $icon,
        public ?Permission $permission = null,
    ) {}

    /**
     * Whether the item may be shown to the given user.
     */
    public function isVisibleTo(?User $user): bool
    {
        if (! Route::has($this->route)) {
            return false;
        }

        if ($this->permission === null) {
            return true;
        }

        return $user?->can($this->permission) ?? false;
    }
}
