<?php

namespace App\Exports;

use App\Exports\Concerns\PreventsFormulaInjection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Base class for the application's spreadsheet exports.
 *
 * The class exports a collection of rows, writes the given headings and
 * protects every user controlled value against spreadsheet formula
 * injection.
 */
abstract class SpreadsheetExport implements FromCollection, WithHeadings
{
    use PreventsFormulaInjection;

    /**
     * The headings of the exported sheet.
     *
     * @return array<int, string>
     */
    abstract public function headings(): array;

    /**
     * The rows of the exported sheet.
     *
     * @return Collection<int, Collection<int, mixed>>
     */
    abstract public function collection(): Collection;

    /**
     * Build a row from a record, sanitising every value.
     *
     * @param  array<int|string, mixed>  $row
     * @return array<int|string, mixed>
     */
    protected function row(array $row): array
    {
        return $this->sanitizeSpreadsheetRow($row);
    }
}
