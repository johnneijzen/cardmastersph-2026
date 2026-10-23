@if ($order === null)
    —
@else
    <div class="flex flex-wrap items-center gap-2">
        <span>{{ $order->due_date?->format('d M Y') ?? '—' }}</span>

        @if ($order->isOverdue())
            <flux:badge color="red" size="sm">{{ __('Overdue') }}</flux:badge>
        @endif
    </div>
@endif
