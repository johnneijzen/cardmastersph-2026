<?php

use App\Support\SqlScript;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('zipcode');
            $table->foreignId('province_id');
            $table->foreign('province_id')->references('id')->on('provinces'); // TODO: check if need cascade
            $table->timestamps();
        });

        SqlScript::run(base_path('/database/seeders/philippine_cities.sql'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cities');
    }
}
