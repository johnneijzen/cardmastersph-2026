<div class="flex items-center justify-end gap-1">
    @can('update', $category)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('categories.edit', $category)"
            :aria-label="__('Edit :name', ['name' => $category->name])"
        />
    @endcan

    @can('delete', $category)
        <flux:button
            size="xs"
            variant="ghost"
            icon="trash"
            color="red"
            wire:click="confirmDelete({{ $category->id }})"
            :aria-label="__('Remove :name', ['name' => $category->name])"
        />
    @endcan
</div>
