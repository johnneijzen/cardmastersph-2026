<?php

namespace Database\Seeders;

class ProvincesSeeder extends SqlSeeder
{
    /**
     * The name of the SQL file inside `database/seeders`.
     */
    protected function sqlFile(): string
    {
        return 'philippine_provinces.sql';
    }

    /**
     * The table the SQL file writes to.
     */
    protected function table(): string
    {
        return 'provinces';
    }
}
