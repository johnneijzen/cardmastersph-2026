<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventorywithdrawalSlipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_withdrawal_slips', function (Blueprint $table) {
            $table->id();
            $table->integer('qty');
            $table->string('particulars');
            $table->foreignId('inventory_id');
            $table->foreign('inventory_id')->references('id')->on('inventory')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('withdrawal_slips_id');
            $table->foreign('withdrawal_slips_id')->references('id')->on('withdrawal_slips')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('inventory_withdrawal_slips');
    }
}
