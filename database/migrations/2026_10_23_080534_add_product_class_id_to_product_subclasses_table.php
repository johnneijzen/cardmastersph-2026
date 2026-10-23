<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The legacy application stored the job order classes and subclasses as
     * two unrelated lists, so a subclass could not say which class it belongs
     * to. The column is nullable because the subclasses that were migrated
     * from the legacy application have not been classified yet, and because
     * archiving a class must never detach the subclasses or the job orders
     * that already point at it.
     */
    public function up(): void
    {
        Schema::table('product_subclasses', function (Blueprint $table) {
            $table->foreignId('product_class_id')
                ->nullable()
                ->after('name')
                ->constrained('product_classes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_subclasses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_class_id');
        });
    }
};
