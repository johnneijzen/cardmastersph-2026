<?php

namespace App\Imports;

use App\Imports\Concerns\CollectsImportResults;
use App\Imports\Exceptions\InvalidSpreadsheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Base class for the application's spreadsheet imports.
 *
 * The class verifies that the required columns are present, numbers the rows,
 * validates every row, writes the rows in transactions and reports a summary
 * including the row level errors.
 *
 * The import can also be given a file in preview mode. The preview runs the
 * very same code inside a transaction that is rolled back afterwards, so the
 * user sees exactly what the import would do without any record being kept.
 */
abstract class SpreadsheetImport implements ToCollection, WithHeadingRow
{
    use CollectsImportResults;

    /**
     * The number of rows that are written before the transaction is closed.
     */
    protected int $chunkSize = 500;

    /**
     * The worksheet row that holds the headings.
     */
    protected int $headingRow = 1;

    /**
     * Whether the import only previews the rows.
     */
    protected bool $previewOnly = false;

    /**
     * The worksheet row that is currently imported.
     */
    protected int $currentRowNumber = 0;

    /**
     * The headings the spreadsheet must contain.
     *
     * The key is the slug the importer reads the column under, the value is
     * the heading as it is shown to the user.
     *
     * @return array<string, string>
     */
    abstract protected function requiredHeadings(): array;

    /**
     * Write a single row.
     *
     * The method calls the matching counting method itself. When it changes
     * nothing it calls `countSkipped()`, and when it rejects the row it calls
     * `rejectRow()`.
     *
     * @param  array<string, mixed>  $row
     */
    abstract protected function importRow(array $row): void;

    /**
     * The label of a row that is shown in the preview.
     *
     * @param  array<string, mixed>  $row
     */
    protected function previewLabel(array $row): ?string
    {
        return null;
    }

    /**
     * The validation rules for a row.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * The validation messages for a row.
     *
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [];
    }

    /**
     * The number of rows that are written per transaction.
     */
    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Only preview the rows, without keeping any change.
     */
    public function preview(): static
    {
        $this->previewOnly = true;

        return $this;
    }

    /**
     * Whether the import only previews the rows.
     */
    public function isPreview(): bool
    {
        return $this->previewOnly;
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $collection
     */
    public function collection($collection): void
    {
        $this->assertHeadingsArePresent($collection);

        $rows = collect($this->numberRows($collection));

        if ($this->previewOnly) {
            DB::beginTransaction();

            try {
                $this->importRows($rows);
            } finally {
                DB::rollBack();
            }

            return;
        }

        $this->importRows($rows);
    }

    /**
     * Import the rows in chunks.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    protected function importRows($rows): void
    {
        $rows->chunk($this->chunkSize)->each(function ($chunk): void {
            $this->importChunk($chunk);
        });
    }

    /**
     * Import a single chunk of rows.
     *
     * @param  Collection<int, array<string, mixed>>  $chunk
     */
    protected function importChunk($chunk): void
    {
        if (! $this->usesTransaction() || $this->previewOnly) {
            $chunk->each(fn (array $row) => $this->importValidatedRow($row));

            return;
        }

        DB::transaction(function () use ($chunk): void {
            $chunk->each(fn (array $row) => $this->importValidatedRow($row));
        });
    }

    /**
     * Whether the rows are written inside a database transaction.
     */
    protected function usesTransaction(): bool
    {
        return true;
    }

    /**
     * Validate and import a single row.
     *
     * @param  array<string, mixed>  $row
     */
    protected function importValidatedRow(array $row): void
    {
        $rowNumber = (int) ($row['__row'] ?? 0);
        $this->currentRowNumber = $rowNumber;

        $validator = Validator::make($row, $this->rules(), $this->customValidationMessages());

        if ($validator->fails()) {
            $messages = array_values($validator->errors()->all());

            foreach ($messages as $message) {
                $this->addRowError($rowNumber, $message);
            }

            $this->countFailed();
            $this->recordPreview($row, 'failed', $messages);

            return;
        }

        $importedBefore = $this->importedCount;
        $updatedBefore = $this->updatedCount;
        $skippedBefore = $this->skippedCount;
        $failedBefore = $this->failedCount;

        try {
            $this->importRow($row);

            if ($this->importedCount === $importedBefore
                && $this->updatedCount === $updatedBefore
                && $this->skippedCount === $skippedBefore
                && $this->failedCount === $failedBefore) {
                $this->countImported();
            }
        } catch (\Throwable $exception) {
            $this->addRowError($rowNumber, $exception->getMessage());
            $this->countFailed();
        }

        $this->recordPreview($row, $this->rowStatus($importedBefore, $updatedBefore, $skippedBefore, $failedBefore));
    }

    /**
     * Reject the row that is currently imported and explain why.
     */
    protected function rejectRow(string $message): void
    {
        $this->addRowError($this->currentRowNumber, $message);
        $this->countFailed();
    }

    /**
     * The status of a row that was just imported.
     */
    protected function rowStatus(int $importedBefore, int $updatedBefore, int $skippedBefore, int $failedBefore): string
    {
        return match (true) {
            $this->failedCount > $failedBefore => 'failed',
            $this->importedCount > $importedBefore => 'imported',
            $this->updatedCount > $updatedBefore => 'updated',
            $this->skippedCount > $skippedBefore => 'skipped',
            default => 'skipped',
        };
    }

    /**
     * Keep the outcome of a row for the preview.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>|null  $messages
     */
    protected function recordPreview(array $row, string $status, ?array $messages = null): void
    {
        $rowNumber = (int) ($row['__row'] ?? 0);

        $this->previewRows[] = [
            'row' => $rowNumber,
            'status' => $status,
            'label' => $this->previewLabel($row),
            'messages' => array_values($messages ?? $this->rowErrors[$rowNumber] ?? []),
        ];
    }

    /**
     * Give every row the worksheet row number it was read from.
     *
     * @param  Collection<int, Collection<string, mixed>>  $collection
     * @return array<int, array<string, mixed>>
     */
    protected function numberRows($collection): array
    {
        return $collection->values()->map(function (Collection $row, int $index): array {
            $values = [];

            foreach ($row->toArray() as $key => $value) {
                $values[(string) $key] = $value;
            }

            $values['__row'] = $index + $this->headingRow + 1;

            return $values;
        })->all();
    }

    /**
     * Make sure the spreadsheet contains every required column.
     *
     * @param  Collection<int, Collection<string, mixed>>  $collection
     */
    protected function assertHeadingsArePresent($collection): void
    {
        $firstRow = $collection->first();

        if ($firstRow === null) {
            throw InvalidSpreadsheet::unreadable();
        }

        $available = array_keys($firstRow->toArray());
        $headings = $this->requiredHeadings();

        $missing = array_values(array_filter(
            array_keys($headings),
            fn (string $heading): bool => ! in_array($heading, $available, true),
        ));

        if ($missing !== []) {
            throw InvalidSpreadsheet::missingHeadings(array_map(
                fn (string $heading): string => $headings[$heading],
                $missing,
            ));
        }
    }
}
