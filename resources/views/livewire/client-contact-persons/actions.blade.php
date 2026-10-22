<div class="flex items-center justify-end gap-1">
    @can('view', $clientContactPerson)
        <flux:button
            size="xs"
            variant="ghost"
            icon="eye"
            :href="route('client-contact-persons.show', $clientContactPerson)"
            :aria-label="__('View :name', ['name' => $clientContactPerson->contact_person])"
        />
    @endcan

    @can('update', $clientContactPerson)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('client-contact-persons.edit', $clientContactPerson)"
            :aria-label="__('Edit :name', ['name' => $clientContactPerson->contact_person])"
        />
    @endcan

    @can('delete', $clientContactPerson)
        <flux:button
            size="xs"
            variant="ghost"
            icon="trash"
            color="red"
            wire:click="confirmDelete({{ $clientContactPerson->id }})"
            :aria-label="__('Remove :name', ['name' => $clientContactPerson->contact_person])"
        />
    @endcan
</div>
