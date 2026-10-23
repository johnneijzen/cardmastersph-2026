<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Hands out the document numbers of the application.
 *
 * A number is only handed out inside a transaction and while the counter row
 * is locked, so two documents that are created at the same time can never
 * receive the same number. The counter starts again every year.
 */
class NumberSequenceService
{
    /**
     * Hand out the next number of the given document.
     *
     * The number is formatted as `<prefix>-<year>-<six digits>`, for example
     * `WS-2026-000042`.
     */
    public function next(string $name, string $prefix, ?CarbonInterface $date = null): string
    {
        $date ??= now();

        $number = $this->nextNumber($name, (int) $date->format('Y'));

        return sprintf('%s-%s-%06d', $prefix, $date->format('Y'), $number);
    }

    /**
     * Hand out the next number of the given document and year.
     */
    public function nextNumber(string $name, int $year): int
    {
        return DB::transaction(function () use ($name, $year): int {
            $sequence = $this->lockSequence($name, $year);

            $number = $sequence['last_number'] + 1;

            DB::table('number_sequences')
                ->where('id', $sequence['id'])
                ->update([
                    'last_number' => $number,
                    'updated_at' => now(),
                ]);

            return $number;
        });
    }

    /**
     * Lock the counter row, creating it when this is the first document of the
     * document type in the year.
     *
     * @return array{id: int, last_number: int}
     */
    protected function lockSequence(string $name, int $year): array
    {
        $sequence = $this->findSequence($name, $year);

        if ($sequence !== null) {
            return $sequence;
        }

        // The unique index on the document and the year makes the insert safe
        // when two requests create the counter at the same time.
        DB::table('number_sequences')->insertOrIgnore([
            'name' => $name,
            'year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->findSequence($name, $year) ?? throw new \RuntimeException(
            "The number sequence [{$name}] for the year [{$year}] could not be created.",
        );
    }

    /**
     * Read and lock the counter row.
     *
     * @return array{id: int, last_number: int}|null
     */
    protected function findSequence(string $name, int $year): ?array
    {
        $sequence = DB::table('number_sequences')
            ->where('name', $name)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            return null;
        }

        return [
            'id' => (int) $sequence->id,
            'last_number' => (int) $sequence->last_number,
        ];
    }
}
