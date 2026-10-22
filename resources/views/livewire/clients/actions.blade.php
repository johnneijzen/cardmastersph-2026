<div class="flex items-center justify-end gap-1">
    @if ($client->trashed())
        @can('restore', $client)
            <flux:button
                size="xs"
                variant="ghost"
                icon="arrow-path"
                wire:click="restoreClient({{ $client->id }})"
                :aria-label="__('Restore :name', ['name' => $client->name])"
            />
        @endcan
    @else
        @can('view', $client)
            <flux:button
                size="xs"
                variant="ghost"
                icon="eye"
                :href="route('clients.show', $client)"
                :aria-label="__('View :name', ['name' => $client->name])"
            />
        @endcan

        @can('update', $client)
            <flux:button
                size="xs"
                variant="ghost"
                icon="pencil-square"
                :href="route('clients.edit', $client)"
                :aria-label="__('Edit :name', ['name' => $client->name])"
            />
        @endcan

        @can('archive', $client)
            <flux:button
                size="xs"
                variant="ghost"
                icon="archive-box"
                color="red"
                wire:click="confirmArchive({{ $client->id }})"
                :aria-label="__('Archive :name', ['name' => $client->name])"
            />
        @endcan
    @endif
</div>
