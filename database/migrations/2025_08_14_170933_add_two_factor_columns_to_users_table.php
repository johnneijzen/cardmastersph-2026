<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->after('password')->nullable();
            }

            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->after('two_factor_secret')->nullable();
            }

            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->after('two_factor_recovery_codes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $isLegacyJetstreamSchema = Schema::hasColumn('users', 'current_team_id');

        Schema::table('users', function (Blueprint $table) use ($isLegacyJetstreamSchema) {
            $columns = $isLegacyJetstreamSchema
                ? ['two_factor_confirmed_at']
                : ['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'];

            $existingColumns = array_filter($columns, fn (string $column): bool => Schema::hasColumn('users', $column));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
