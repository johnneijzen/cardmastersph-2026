<div class="flex items-center justify-end gap-1">
    @can('view', $clientAgent)
        <flux:button
            size="xs"
            variant="ghost"
            icon="eye"
            :href="route('client-agents.show', $clientAgent)"
            :aria-label="__('View :name', ['name' => $clientAgent->name])"
        />
    @endcan

    @can('update', $clientAgent)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('client-agents.edit', $clientAgent)"
            :aria-label="__('Edit :name', ['name' => $clientAgent->name])"
        />
    @endcan

    @can('delete', $clientAgent)
        <flux:button
            size="xs"
            variant="ghost"
            icon="trash"
            color="red"
            wire:click="confirmDelete({{ $clientAgent->id }})"
            :aria-label="__('Remove :name', ['name' => $clientAgent->name])"
        />
    @endcan
</div>
