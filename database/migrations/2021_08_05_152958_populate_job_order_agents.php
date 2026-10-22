<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PopulateJobOrderAgents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_order_agents', function (Blueprint $table) {
            //
        });

        DB::unprepared(File::get(base_path().'/database/seeders/job_order_agents.sql'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_order_agents', function (Blueprint $table) {
            //
        });
    }
}
