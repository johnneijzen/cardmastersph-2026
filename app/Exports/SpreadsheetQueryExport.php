<?php

namespace App\Exports;

use App\Exports\Concerns\PreventsFormulaInjection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Base class for the application's spreadsheet exports that read from a query.
 *
 * The class walks the query in chunks, so a large export never loads every
 * record into memory, writes the given headings and protects every user
 * controlled value against spreadsheet formula injection.
 */
abstract class SpreadsheetQueryExport implements FromQuery, WithHeadings, WithMapping
{
    use PreventsFormulaInjection;

    /**
     * The headings of the exported sheet.
     *
     * @return array<int, string>
     */
    abstract public function headings(): array;

    /**
     * The query behind the export.
     *
     * The query needs a deterministic order with a unique tie breaker,
     * because the export walks it in chunks.
     *
     * @return Builder|EloquentBuilder<covariant \Illuminate\Database\Eloquent\Model>
     */
    abstract public function query(): Builder|EloquentBuilder;

    /**
     * Build a row from a record.
     *
     * @return array<int|string, mixed>
     */
    abstract public function map(mixed $row): array;

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
