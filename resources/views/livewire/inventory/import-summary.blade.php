@php
    /** @var array{imported: int, updated: int, skipped: int, failed: int, errors: array<int, array<int, string>>} $summary */
@endphp

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:text>{{ __('Added') }}</flux:text>
        <p class="text-2xl font-semibold">{{ number_format($summary['imported']) }}</p>
    </div>

    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:text>{{ __('Updated') }}</flux:text>
        <p class="text-2xl font-semibold">{{ number_format($summary['updated']) }}</p>
    </div>

    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:text>{{ __('Unchanged') }}</flux:text>
        <p class="text-2xl font-semibold">{{ number_format($summary['skipped']) }}</p>
    </div>

    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:text>{{ __('Rejected') }}</flux:text>
        <p class="text-2xl font-semibold">{{ number_format($summary['failed']) }}</p>
    </div>
</div>

@if ($summary['failed'] > 0)
    <flux:callout color="amber" icon="exclamation-triangle" class="mt-4">
        <flux:text>
            {{ __('The rejected rows are listed in the table below. Correct them in the spreadsheet and import the file again.') }}
        </flux:text>
    </flux:callout>
@endif
