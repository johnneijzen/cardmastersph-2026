<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_receipts', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('or_number');
            $table->foreignId('client_id')->nullable($value = true);
            $table->foreign('client_id')->references('id')->on('clients')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('job_order_id')->nullable($value = true);
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onUpdate('cascade')->onDelete('set null');
            $table->decimal('amount', 13, 2);
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
        Schema::dropIfExists('cash_receipts');
    }
}
