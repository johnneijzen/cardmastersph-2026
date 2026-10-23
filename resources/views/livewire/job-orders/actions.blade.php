<div class="flex items-center justify-end gap-1">
    <flux:button
        size="xs"
        variant="ghost"
        icon="printer"
        :href="route('job-orders.print', $jobOrder)"
        target="_blank"
        :aria-label="__('Print the invoice of :number', ['number' => $jobOrder->job_order_number ?? $jobOrder->id])"
    />

    <flux:button
        size="xs"
        variant="ghost"
        icon="document-text"
        :href="route('job-orders.routing-slip', $jobOrder)"
        target="_blank"
        :aria-label="__('Print the routing slip of :number', ['number' => $jobOrder->job_order_number ?? $jobOrder->id])"
    />

    <flux:button
        size="xs"
        variant="ghost"
        icon="eye"
        :href="route('job-orders.show', $jobOrder)"
        :aria-label="__('View :number', ['number' => $jobOrder->job_order_number ?? $jobOrder->id])"
    />

    @can('update', $jobOrder)
        <flux:button
            size="xs"
            variant="ghost"
            icon="pencil-square"
            :href="route('job-orders.edit', $jobOrder)"
            :aria-label="__('Edit :number', ['number' => $jobOrder->job_order_number ?? $jobOrder->id])"
        />
    @endcan

    @can('archive', $jobOrder)
        <flux:button
            size="xs"
            variant="ghost"
            icon="archive-box"
            color="red"
            wire:click="confirmArchive({{ $jobOrder->id }})"
            :aria-label="__('Archive :number', ['number' => $jobOrder->job_order_number ?? $jobOrder->id])"
        />
    @endcan
</div>
