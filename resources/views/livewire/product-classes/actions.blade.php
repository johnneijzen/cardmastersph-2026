<div class="flex items-center justify-end gap-1">
    <flux:button
        size="xs"
        variant="ghost"
        icon="eye"
        :href="route('product-classes.show', $productClass)"
        :aria-label="__('View :name', ['name' => $productClass->name])"
    />

    @can('update', $productClass)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('product-classes.edit', $productClass)"
            :aria-label="__('Edit :name', ['name' => $productClass->name])"
        />
    @endcan

    @can('delete', $productClass)
        <flux:button
            size="xs"
            variant="ghost"
            icon="archive-box"
            color="red"
            wire:click="confirmArchive({{ $productClass->id }})"
            :aria-label="__('Archive :name', ['name' => $productClass->name])"
        />
    @endcan
</div>
