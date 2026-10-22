<?php

namespace Database\Seeders;

use App\Support\SqlScript;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Seeds a table from one of the raw SQL files in `database/seeders`.
 *
 * The SQL files carry the reference data of the legacy application. They are
 * skipped when the table already holds records, so the seeder can be run on a
 * fresh installation as well as on a database that was migrated from the
 * legacy system.
 */
abstract class SqlSeeder extends Seeder
{
    /**
     * The name of the SQL file inside `database/seeders`.
     */
    abstract protected function sqlFile(): string;

    /**
     * The table the SQL file writes to.
     */
    abstract protected function table(): string;

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable($this->table())) {
            return;
        }

        if (DB::table($this->table())->count() > 0) {
            return;
        }

        $path = database_path('seeders/'.$this->sqlFile());

        if (! File::exists($path)) {
            $this->command?->warn("Missing seed file [{$path}].");

            return;
        }

        SqlScript::run($path);
    }
}
