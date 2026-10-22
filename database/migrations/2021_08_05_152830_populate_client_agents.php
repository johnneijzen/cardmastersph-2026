<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PopulateClientAgents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_agents', function (Blueprint $table) {
            //
        });

        DB::unprepared(File::get(base_path().'/database/seeders/client_agents.sql'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_agents', function (Blueprint $table) {
            //
        });
    }
}
