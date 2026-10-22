<?php

namespace App\Exports\Concerns;

/**
 * Protects exported spreadsheets against formula injection.
 *
 * A cell whose text starts with `=`, `+`, `-`, `@`, a tab or a carriage
 * return is interpreted as a formula by spreadsheet applications. Prefixing
 * the value with an apostrophe keeps it plain text.
 */
trait PreventsFormulaInjection
{
    /**
     * The characters that make a spreadsheet treat a cell as a formula.
     *
     * @var array<int, string>
     */
    protected array $formulaPrefixes = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Make a single value safe to write into a spreadsheet cell.
     */
    protected function sanitizeSpreadsheetValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        // Numbers are written as numbers, never as text, so they are safe.
        if (is_numeric($value)) {
            return $value;
        }

        foreach ($this->formulaPrefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return "'".$value;
            }
        }

        return $value;
    }

    /**
     * Make a whole row safe to write into a spreadsheet.
     *
     * @param  array<int|string, mixed>  $row
     * @return array<int|string, mixed>
     */
    protected function sanitizeSpreadsheetRow(array $row): array
    {
        foreach ($row as $key => $value) {
            $row[$key] = $this->sanitizeSpreadsheetValue($value);
        }

        return $row;
    }
}
