<?php

namespace App\Imports\Exceptions;

use RuntimeException;

class InvalidSpreadsheet extends RuntimeException
{
    /**
     * The file does not contain the columns the import needs.
     *
     * @param  array<int, string>  $missing
     */
    public static function missingHeadings(array $missing): self
    {
        return new self('The spreadsheet is missing the required columns: '.implode(', ', $missing).'.');
    }

    /**
     * The file cannot be read as a spreadsheet.
     */
    public static function unreadable(): self
    {
        return new self('The spreadsheet could not be read.');
    }
}
