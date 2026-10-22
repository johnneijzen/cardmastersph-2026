<?php

namespace App\Imports;

use App\Imports\Concerns\CollectsImportResults;
use App\Imports\Exceptions\InvalidSpreadsheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Base class for the application's spreadsheet imports.
 *
 * The class verifies that the required columns are present, validates every
 * row, writes the rows in transactions and reports a summary including the
 * row level errors.
 */
abstract class SpreadsheetImport implements ToCollection, WithHeadingRow, WithValidation
{
    use CollectsImportResults;

    /**
     * The number of rows that are written before the transaction is closed.
     */
    protected int $chunkSize = 500;

    /**
     * The headings the spreadsheet must contain, keyed by the slug the
     * importer reads them under.
     *
     * @return array<string, string>
     */
    abstract protected function requiredHeadings(): array;

    /**
     * Write a single row.
     *
     * @param  array<string, mixed>  $row
     */
    abstract protected function importRow(array $row): void;

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
     * Whether the rows are written inside a database transaction.
     */
    protected function usesTransaction(): bool
    {
        return true;
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $collection
     */
    public function collection($collection): void
    {
        $this->assertHeadingsArePresent($collection);

        $collection->chunk($this->chunkSize())->each(function ($chunk): void {
            $this->importChunk($chunk);
        });
    }

    /**
     * Import a single chunk of rows.
     *
     * @param  Collection<int, Collection<string, mixed>>  $chunk
     */
    protected function importChunk($chunk): void
    {
        if (! $this->usesTransaction()) {
            $chunk->each(fn ($row) => $this->importValidatedRow($row->toArray()));

            return;
        }

        DB::transaction(function () use ($chunk): void {
            $chunk->each(fn ($row) => $this->importValidatedRow($row->toArray()));
        });
    }

    /**
     * Validate and import a single row.
     *
     * @param  array<string, mixed>  $row
     */
    protected function importValidatedRow(array $row): void
    {
        $rowNumber = (int) ($row['__row'] ?? 0);

        $validator = Validator::make($row, $this->rules(), $this->customValidationMessages());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->addRowError($rowNumber, $message);
            }

            $this->countSkipped();

            return;
        }

        try {
            $this->importRow($row);

            $this->countImported();
        } catch (\Throwable $exception) {
            $this->addRowError($rowNumber, $exception->getMessage());
            $this->countSkipped();
        }
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

        $missing = array_values(array_filter(
            array_keys($this->requiredHeadings()),
            fn (string $heading): bool => ! in_array($heading, $available, true),
        ));

        if ($missing !== []) {
            throw InvalidSpreadsheet::missingHeadings($missing);
        }
    }
}
