<?php

namespace App\Imports\Concerns;

/**
 * Collects the outcome of a spreadsheet import.
 *
 * Every import reports how many rows were created, updated or skipped and
 * keeps the row level errors so the user can be told exactly which rows
 * failed and why.
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
     * The number of rows that were left untouched.
     */
    protected int $skippedCount = 0;

    /**
     * The row level errors, keyed by the spreadsheet row number.
     *
     * @var array<int, array<int, string>>
     */
    protected array $rowErrors = [];

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
     * Record that a row was skipped.
     */
    protected function countSkipped(): void
    {
        $this->skippedCount++;
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
            'failed' => count($this->rowErrors),
            'errors' => $this->rowErrors,
        ];
    }
}
