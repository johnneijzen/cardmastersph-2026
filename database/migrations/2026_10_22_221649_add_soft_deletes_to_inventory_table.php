<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An inventory item is archived instead of deleted, so the stock movements
     * and the withdrawal slips that point at it keep their history.
     */
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
