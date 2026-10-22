<?php

namespace Database\Seeders;

class PaymentOptionsSeeder extends SqlSeeder
{
    /**
     * The name of the SQL file inside `database/seeders`.
     */
    protected function sqlFile(): string
    {
        return 'payment_options.sql';
    }

    /**
     * The table the SQL file writes to.
     */
    protected function table(): string
    {
        return 'payment_options';
    }
}
