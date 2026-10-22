<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentOptionToCashReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_receipts', function (Blueprint $table) {
            $table->foreignId('payment_option_id')->nullable($value = true);
            $table->foreign('payment_option_id')->references('id')->on('payment_options')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_receipts', function (Blueprint $table) {
            //
        });
    }
}
