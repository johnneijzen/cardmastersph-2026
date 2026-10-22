<?php

namespace App\Support\Navigation;

use App\Models\User;

/**
 * A group of navigation entries.
 */
final readonly class NavigationGroup
{
    /**
     * @param  list<NavigationItem>  $items
     */
    public function __construct(
        public string $heading,
        public array $items,
    ) {}

    /**
     * The items of the group that are visible to the given user.
     *
     * @return list<NavigationItem>
     */
    public function visibleItems(?User $user): array
    {
        return array_values(array_filter(
            $this->items,
            fn (NavigationItem $item): bool => $item->isVisibleTo($user),
        ));
    }
}
