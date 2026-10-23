<div class="flex items-center justify-end gap-1">
    <flux:button
        size="xs"
        variant="ghost"
        icon="eye"
        :href="route('product-subclasses.show', $productSubclass)"
        :aria-label="__('View :name', ['name' => $productSubclass->name])"
    />

    @can('update', $productSubclass)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('product-subclasses.edit', $productSubclass)"
            :aria-label="__('Edit :name', ['name' => $productSubclass->name])"
        />
    @endcan

    @can('delete', $productSubclass)
        <flux:button
            size="xs"
            variant="ghost"
            icon="archive-box"
            color="red"
            wire:click="confirmArchive({{ $productSubclass->id }})"
            :aria-label="__('Archive :name', ['name' => $productSubclass->name])"
        />
    @endcan
</div>
