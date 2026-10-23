@php
    $color = match ($label) {
        'Completed: Claimed', 'Completed: Not Yet Claimed' => 'green',
        'In Production' => 'blue',
        'Assigned: In Progress', 'Assigned: On Hold' => 'amber',
        default => 'zinc',
    };
@endphp

<flux:badge :color="$color" size="sm">{{ $label }}</flux:badge>
