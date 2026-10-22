<?php

namespace Database\Seeders;

class JobOrderAgentsSeeder extends SqlSeeder
{
    /**
     * The name of the SQL file inside `database/seeders`.
     */
    protected function sqlFile(): string
    {
        return 'job_order_agents.sql';
    }

    /**
     * The table the SQL file writes to.
     */
    protected function table(): string
    {
        return 'job_order_agents';
    }
}
