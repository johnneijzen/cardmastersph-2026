<?php

use App\Imports\Exceptions\InvalidSpreadsheet;
use App\Imports\InventoryImport;
use App\Models\Inventory;
use App\Rules\SpreadsheetUpload;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new #[Title('Import inventory')] class extends Component
{
    use WithFileUploads;

    /**
     * The spreadsheet that was chosen for the import.
     */
    public ?TemporaryUploadedFile $file = null;

    /**
     * The private location of the uploaded spreadsheet.
     */
    #[Locked]
    public string $stored_path = '';

    /**
     * Whether the preview of the import is shown.
     */
    public bool $previewing = false;

    /**
     * The summary of the preview.
     *
     * @var array{imported: int, updated: int, skipped: int, failed: int, errors: array<int, array<int, string>>}|null
     */
    public ?array $preview = null;

    /**
     * The outcome of every row of the preview.
     *
     * @var list<array{row: int, status: string, label: string|null, messages: list<string>}>
     */
    public array $rows = [];

    /**
     * The summary of the import that was written.
     *
     * @var array{imported: int, updated: int, skipped: int, failed: int, errors: array<int, array<int, string>>}|null
     */
    public ?array $result = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('import', Inventory::class);
    }

    /**
     * Read the spreadsheet and show what the import would do.
     *
     * Nothing is written: the import runs inside a transaction that is rolled
     * back, so the preview can report duplicates and conflicts accurately.
     */
    public function previewImport(): void
    {
        Gate::authorize('import', Inventory::class);

        $this->validate([
            'file' => ['required', new SpreadsheetUpload],
        ]);

        $this->discardStoredFile();

        $path = $this->file?->store('inventory-imports', 'local');

        if (! is_string($path)) {
            $this->addError('file', __('The spreadsheet could not be stored.'));

            return;
        }

        $this->stored_path = $path;

        try {
            $importer = $this->importSpreadsheet();
            $importer->preview();

            Excel::import($importer, Storage::disk('local')->path($path));
        } catch (InvalidSpreadsheet $exception) {
            $this->discardStoredFile();
            $this->addError('file', $exception->getMessage());

            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('file', __('The spreadsheet could not be processed.'));

            return;
        }

        $this->preview = $importer->summary();
        $this->rows = $importer->rowPreviews();
        $this->previewing = true;
        $this->result = null;
    }

    /**
     * Write the spreadsheet that was previewed.
     */
    public function confirmImport(): void
    {
        Gate::authorize('import', Inventory::class);

        if ($this->stored_path === '' || ! Storage::disk('local')->exists($this->stored_path)) {
            $this->addError('file', __('The spreadsheet is no longer available. Choose the file again.'));

            return;
        }

        try {
            $importer = $this->importSpreadsheet();

            Excel::import($importer, Storage::disk('local')->path($this->stored_path));
        } catch (InvalidSpreadsheet $exception) {
            $this->discardStoredFile();
            $this->addError('file', $exception->getMessage());

            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('file', __('The spreadsheet could not be processed.'));

            return;
        }

        $this->result = $importer->summary();
        $this->rows = $importer->rowPreviews();
        $this->preview = null;
        $this->previewing = false;

        $this->discardStoredFile();
        $this->file = null;

        Flux::toast(variant: 'success', text: __('The inventory import was processed.'));
    }

    /**
     * Drop the spreadsheet that was uploaded for the preview.
     */
    public function cancelImport(): void
    {
        $this->discardStoredFile();
        $this->file = null;
        $this->previewing = false;
        $this->preview = null;
        $this->rows = [];
        $this->result = null;
        $this->resetErrorBag();
    }

    /**
     * The importer that writes the rows for the signed in user.
     */
    protected function importSpreadsheet(): InventoryImport
    {
        $actorId = auth()->id();

        return new InventoryImport($actorId === null ? null : (int) $actorId);
    }

    /**
     * Remove the uploaded spreadsheet from the private storage.
     */
    protected function discardStoredFile(): void
    {
        if ($this->stored_path !== '' && Storage::disk('local')->exists($this->stored_path)) {
            Storage::disk('local')->delete($this->stored_path);
        }

        $this->stored_path = '';
    }

    /**
     * The headings the spreadsheet has to contain.
     *
     * @return array<string, string>
     */
    public function headings(): array
    {
        return [
            'Item Name' => __('Required'),
            'Category Name' => __('Required, the category has to exist'),
            'Subcategory Name' => __('Required, the subcategory has to belong to the category'),
            'Current Quantity' => __('Required, a whole number, never below zero'),
            'Description' => __('Optional'),
            'Price' => __('Optional'),
            'Size' => __('Optional'),
            'Color' => __('Optional'),
            'Type' => __('Optional'),
        ];
    }

    /**
     * The rows that are shown in the preview table.
     *
     * @return list<array{row: int, status: string, label: string|null, messages: list<string>}>
     */
    public function visibleRows(): array
    {
        return array_slice($this->rows, 0, 200);
    }

    /**
     * A short label for the status of a row.
     */
    public function statusLabel(string $status): string
    {
        return match ($status) {
            'imported' => __('Will be added'),
            'updated' => __('Will be updated'),
            'skipped' => __('Unchanged'),
            'failed' => __('Rejected'),
            default => Str::headline($status),
        };
    }

    /**
     * A short label for the status of a row after the import was written.
     */
    public function resultLabel(string $status): string
    {
        return match ($status) {
            'imported' => __('Added'),
            'updated' => __('Updated'),
            'skipped' => __('Unchanged'),
            'failed' => __('Rejected'),
            default => Str::headline($status),
        };
    }

    /**
     * The colour of the badge of a row status.
     */
    public function statusColor(string $status): string
    {
        return match ($status) {
            'imported' => 'lime',
            'updated' => 'blue',
            'skipped' => 'zinc',
            'failed' => 'red',
            default => 'zinc',
        };
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Import inventory') }}</flux:heading>
            <flux:text class="mt-1">
                {{ __('Add inventory items from a spreadsheet. Every row is checked before anything is written.') }}
            </flux:text>
        </div>

        <flux:button :href="route('inventory.index')" variant="ghost">
            {{ __('Back to the inventory') }}
        </flux:button>
    </header>

    <flux:card>
        <form wire:submit="previewImport" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Choose the spreadsheet') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('An .xlsx, .xls or .csv file of at most 10 megabytes. The first row holds the headings.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model="file"
                type="file"
                accept=".xlsx,.xls,.csv"
                :label="__('Spreadsheet')"
                :error="$errors->first('file')"
            />

            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-left dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 font-medium">{{ __('Column') }}</th>
                            <th class="px-4 py-2 font-medium">{{ __('Meaning') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->headings() as $heading => $meaning)
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ $heading }}</td>
                                <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $meaning }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <flux:button type="submit" variant="primary" icon="eye" wire:loading.attr="disabled">
                    {{ __('Preview the import') }}
                </flux:button>
            </div>
        </form>
    </flux:card>

    @if ($previewing && $preview !== null)
        <flux:card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ __('Preview') }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ __('Nothing has been written yet. Confirm the import to keep the rows that are shown as added or updated.') }}
                    </flux:text>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button wire:click="cancelImport" variant="ghost" wire:loading.attr="disabled">
                        {{ __('Cancel') }}
                    </flux:button>

                    <flux:button wire:click="confirmImport" variant="primary" icon="check" wire:loading.attr="disabled">
                        {{ __('Confirm the import') }}
                    </flux:button>
                </div>
            </div>

            @include('livewire.inventory.import-summary', ['summary' => $preview])
            @include('livewire.inventory.import-rows', ['rows' => $this->visibleRows(), 'total' => count($rows), 'previewing' => true])
        </flux:card>
    @endif

    @if ($result !== null)
        <flux:card>
            <flux:heading size="lg">{{ __('Import result') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The rows that were shown as added or updated are now in the inventory.') }}</flux:text>

            @include('livewire.inventory.import-summary', ['summary' => $result])
            @include('livewire.inventory.import-rows', ['rows' => $this->visibleRows(), 'total' => count($rows), 'previewing' => false])
        </flux:card>
    @endif
</div>
