@php
    /** @var list<array{row: int, status: string, label: string|null, messages: list<string>}> $rows */
    /** @var int $total */
    /** @var bool $previewing */
@endphp

@if ($rows === [])
    <flux:text class="mt-6">{{ __('The spreadsheet does not hold any row.') }}</flux:text>
@else
    <div class="mt-6 overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Row') }}</flux:table.column>
                <flux:table.column>{{ __('Item') }}</flux:table.column>
                <flux:table.column>{{ __('Outcome') }}</flux:table.column>
                <flux:table.column>{{ __('Details') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($rows as $row)
                    <flux:table.row :key="$row['row'].'-'.$loop->index">
                        <flux:table.cell>{{ $row['row'] }}</flux:table.cell>
                        <flux:table.cell>{{ $row['label'] ?: '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$this->statusColor($row['status'])" size="sm">
                                @if ($previewing)
                                    {{ $this->statusLabel($row['status']) }}
                                @else
                                    {{ $this->resultLabel($row['status']) }}
                                @endif
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @forelse ($row['messages'] as $message)
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @empty
                                —
                            @endforelse
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($total > count($rows))
        <flux:text class="mt-4">
            {{ __('Showing the first :shown of :total rows.', ['shown' => count($rows), 'total' => $total]) }}
        </flux:text>
    @endif
@endif
