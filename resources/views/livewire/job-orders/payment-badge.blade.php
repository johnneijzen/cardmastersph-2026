@php
    $color = match ($status) {
        'Fully Paid' => 'green',
        'Partially Paid' => 'amber',
        'Unpaid' => 'red',
        default => 'zinc',
    };
@endphp

<flux:badge :color="$color" size="sm">{{ $status ?? '—' }}</flux:badge>
