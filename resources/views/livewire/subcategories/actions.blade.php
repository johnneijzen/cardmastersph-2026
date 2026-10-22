<div class="flex items-center justify-end gap-1">
    @can('update', $subcategory)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('subcategories.edit', $subcategory)"
            :aria-label="__('Edit :name', ['name' => $subcategory->name])"
        />
    @endcan

    @can('delete', $subcategory)
        <flux:button
            size="xs"
            variant="ghost"
            icon="trash"
            color="red"
            wire:click="confirmDelete({{ $subcategory->id }})"
            :aria-label="__('Remove :name', ['name' => $subcategory->name])"
        />
    @endcan
</div>
