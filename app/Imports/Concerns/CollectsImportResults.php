<?php

namespace App\Imports\Concerns;

/**
 * Collects the outcome of a spreadsheet import.
 *
 * Every import reports how many rows were created, updated, left untouched or
 * rejected and keeps the row level errors, so the user can be told exactly
 * which rows failed and why.
 */
trait CollectsImportResults
{
    /**
     * The number of rows that were created.
     */
    protected int $importedCount = 0;

    /**
     * The number of rows that were updated.
     */
    protected int $updatedCount = 0;

    /**
     * The number of rows that were valid but changed nothing.
     */
    protected int $skippedCount = 0;

    /**
     * The number of rows that were rejected.
     */
    protected int $failedCount = 0;

    /**
     * The row level errors, keyed by the spreadsheet row number.
     *
     * @var array<int, array<int, string>>
     */
    protected array $rowErrors = [];

    /**
     * The outcome of every row, in the order of the spreadsheet.
     *
     * @var list<array{row: int, status: string, label: string|null, messages: list<string>}>
     */
    protected array $previewRows = [];

    /**
     * Record that a row was created.
     */
    protected function countImported(): void
    {
        $this->importedCount++;
    }

    /**
     * Record that a row was updated.
     */
    protected function countUpdated(): void
    {
        $this->updatedCount++;
    }

    /**
     * Record that a row was left untouched.
     */
    protected function countSkipped(): void
    {
        $this->skippedCount++;
    }

    /**
     * Record that a row was rejected.
     */
    protected function countFailed(): void
    {
        $this->failedCount++;
    }

    /**
     * Record a row level error.
     */
    protected function addRowError(int $row, string $message): void
    {
        $this->rowErrors[$row][] = $message;
    }

    /**
     * The row level errors.
     *
     * @return array<int, array<int, string>>
     */
    public function rowErrors(): array
    {
        return $this->rowErrors;
    }

    /**
     * The outcome of every row.
     *
     * @return list<array{row: int, status: string, label: string|null, messages: list<string>}>
     */
    public function rowPreviews(): array
    {
        return $this->previewRows;
    }

    /**
     * The summary of the import.
     *
     * @return array{imported: int, updated: int, skipped: int, failed: int, errors: array<int, array<int, string>>}
     */
    public function summary(): array
    {
        return [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'failed' => $this->failedCount,
            'errors' => $this->rowErrors,
        ];
    }
}
