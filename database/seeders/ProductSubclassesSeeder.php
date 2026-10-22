<?php

namespace Database\Seeders;

class ProductSubclassesSeeder extends SqlSeeder
{
    /**
     * The name of the SQL file inside `database/seeders`.
     */
    protected function sqlFile(): string
    {
        return 'subclasses.sql';
    }

    /**
     * The table the SQL file writes to.
     */
    protected function table(): string
    {
        return 'product_subclasses';
    }
}
