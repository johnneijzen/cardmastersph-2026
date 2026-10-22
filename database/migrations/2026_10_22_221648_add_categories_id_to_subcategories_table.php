<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The legacy application stored the categories and the subcategories as
     * two unrelated lists, so a subcategory could not say which category it
     * belongs to. The column is nullable because the subcategories that were
     * migrated from the legacy application have not been classified yet.
     */
    public function up(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->foreignId('categories_id')
                ->nullable()
                ->after('name')
                ->constrained('categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categories_id');
        });
    }
};
