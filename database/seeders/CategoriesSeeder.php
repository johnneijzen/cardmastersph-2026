<?php

namespace Database\Seeders;

class CategoriesSeeder extends SqlSeeder
{
    /**
     * The name of the SQL file inside `database/seeders`.
     */
    protected function sqlFile(): string
    {
        return 'categories.sql';
    }

    /**
     * The table the SQL file writes to.
     */
    protected function table(): string
    {
        return 'categories';
    }
}
