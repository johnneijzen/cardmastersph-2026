<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatewithdrawalSlipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrawal_slips', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('client_id')->nullable($value = true);
            $table->foreign('client_id')->references('id')->on('clients')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('job_order_id')->nullable($value = true);
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('created_by')->nullable($value = true);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable($value = true);
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
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
        Schema::dropIfExists('withdrawal_slips');
    }
}
