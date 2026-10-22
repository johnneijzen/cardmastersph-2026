<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PopulateSubclassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subclasses', function (Blueprint $table) {
            //
        });
        DB::unprepared(File::get(base_path().'/database/seeders/subclasses.sql'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subclasses', function (Blueprint $table) {
            //
        });
    }
}
