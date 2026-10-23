<div class="flex items-center justify-end gap-1">
    @can('view', $withdrawalSlip)
        <flux:button
            size="xs"
            variant="ghost"
            icon="eye"
            :href="route('withdrawal-slips.show', $withdrawalSlip)"
            :aria-label="__('View :number', ['number' => $withdrawalSlip->slip_number])"
        />

        <flux:button
            size="xs"
            variant="ghost"
            icon="printer"
            :href="route('withdrawal-slips.print', $withdrawalSlip)"
            target="_blank"
            :aria-label="__('Print :number', ['number' => $withdrawalSlip->slip_number])"
        />
    @endcan

    @can('delete', $withdrawalSlip)
        <flux:button
            size="xs"
            variant="ghost"
            icon="arrow-uturn-left"
            color="red"
            wire:click="confirmReverse({{ $withdrawalSlip->id }})"
            :aria-label="__('Reverse :number', ['number' => $withdrawalSlip->slip_number])"
        />
    @endcan
</div>
