<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureClientsNameIsUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * A client name identifies a client for the whole life of the record, so
     * it is unique. Archived clients keep their name reserved, which is why
     * the index covers the archived rows as well.
     *
     * The migrated database already carries the index under a different name,
     * so the index is only added when it is missing.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasIndex('clients', ['name'], 'unique')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->unique('name', 'clients_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasIndex('clients', 'clients_name_unique')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_name_unique');
        });
    }
}
