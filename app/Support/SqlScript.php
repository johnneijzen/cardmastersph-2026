<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Runs a trusted SQL script from the repository.
 *
 * The scripts contain several statements separated by semicolons, which the
 * prepared statement API cannot execute. The script is therefore handed to
 * the driver directly, exactly like `DB::unprepared()` does, while keeping
 * the "pretend" mode of the migrator working.
 */
final class SqlScript
{
    /**
     * Execute the SQL file at the given path.
     */
    public static function run(string $path): void
    {
        $connection = DB::connection();

        if ($connection->pretending()) {
            return;
        }

        if (! File::exists($path)) {
            throw new \RuntimeException("The SQL script [{$path}] does not exist.");
        }

        $connection->getPdo()->exec(File::get($path));
    }
}
