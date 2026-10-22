<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->string('item_name')->nullable($value = true)->change();
            $table->string('description')->nullable($value = true)->change();
            $table->string('price')->nullable($value = true)->change();
            $table->string('size')->nullable($value = true)->change();
            $table->string('color')->nullable($value = true)->change();
            $table->string('type')->nullable($value = true)->change();
            $table->string('current_quantity')->nullable($value = true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->string('item_name')->nullable($value = false)->change();
            $table->string('description')->nullable($value = false)->change();
            $table->string('price')->nullable($value = false)->change();
            $table->string('size')->nullable($value = false)->change();
            $table->string('color')->nullable($value = false)->change();
            $table->string('type')->nullable($value = false)->change();
            $table->string('current_quantity')->nullable($value = false)->change();
        });
    }
}
