<div class="flex items-center justify-end gap-1">
    @can('view', $inventory)
        <flux:button
            size="xs"
            variant="ghost"
            icon="eye"
            :href="route('inventory.show', $inventory)"
            :aria-label="__('View :name', ['name' => $inventory->item_name])"
        />
    @endcan

    @can('update', $inventory)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('inventory.edit', $inventory)"
            :aria-label="__('Edit :name', ['name' => $inventory->item_name])"
        />
    @endcan

    @can('delete', $inventory)
        <flux:button
            size="xs"
            variant="ghost"
            icon="trash"
            color="red"
            wire:click="confirmDelete({{ $inventory->id }})"
            :aria-label="__('Remove :name', ['name' => $inventory->item_name])"
        />
    @endcan
</div>
