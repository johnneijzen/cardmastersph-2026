<div class="flex items-center justify-end gap-1">
    @can('view', $employee)
        <flux:button
            size="xs"
            variant="ghost"
            icon="eye"
            :href="route('employees.show', $employee)"
            :aria-label="__('View :name', ['name' => $employee->getFullNameAttribute()])"
        />
    @endcan

    @can('update', $employee)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('employees.edit', $employee)"
            :aria-label="__('Edit :name', ['name' => $employee->getFullNameAttribute()])"
        />
    @endcan

    @can('delete', $employee)
        <flux:button
            size="xs"
            variant="ghost"
            icon="trash"
            color="red"
            wire:click="confirmDelete({{ $employee->id }})"
            :aria-label="__('Remove :name', ['name' => $employee->getFullNameAttribute()])"
        />
    @endcan
</div>
