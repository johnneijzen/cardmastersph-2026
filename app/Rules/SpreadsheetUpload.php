<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates an uploaded spreadsheet.
 *
 * The rule checks the file extension, the actual contents of the file and
 * the file size. The extension alone is never trusted, because it is chosen
 * by the person uploading the file.
 */
class SpreadsheetUpload implements ValidationRule
{
    /**
     * @param  array<int, string>  $extensions
     */
    public function __construct(
        protected array $extensions = ['xlsx', 'xls', 'csv'],
        protected int $maxKilobytes = 10240,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a file.');

            return;
        }

        if (! $value->isValid()) {
            $fail('The :attribute was not uploaded successfully.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, $this->extensions, true)) {
            $fail('The :attribute must be a file of type: '.implode(', ', $this->extensions).'.');

            return;
        }

        if (! in_array($value->getMimeType(), $this->allowedMimeTypes(), true)) {
            $fail('The :attribute contains a file that is not a valid spreadsheet.');

            return;
        }

        if ($value->getSize() > $this->maxKilobytes * 1024) {
            $fail('The :attribute may not be larger than '.$this->maxKilobytes.' kilobytes.');
        }
    }

    /**
     * The mime types that may be uploaded for the accepted extensions.
     *
     * @return array<int, string>
     */
    protected function allowedMimeTypes(): array
    {
        return match (true) {
            in_array('csv', $this->extensions, true) => [
                'text/csv',
                'text/plain',
                'application/csv',
                'application/vnd.ms-excel',
            ],
            in_array('xlsx', $this->extensions, true) => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
            ],
            in_array('xls', $this->extensions, true) => [
                'application/vnd.ms-excel',
                'application/octet-stream',
            ],
            default => [],
        };
    }
}
