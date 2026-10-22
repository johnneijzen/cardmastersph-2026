<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('description');
            $table->foreignId('categories_id')->nullable($value = true);
            $table->foreign('categories_id')->references('id')->on('categories');
            $table->foreignId('subcategories_id')->nullable($value = true);
            $table->foreign('subcategories_id')->references('id')->on('subcategories');
            $table->string('price');
            $table->string('size');
            $table->string('color');
            $table->string('type');
            $table->string('current_quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory');
    }
}
