<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add indexes if the columns exist (name might already exist)
            if (Schema::hasColumn('users', 'name')) {
                $table->index('name', 'users_name_search_idx');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->index('role', 'users_role_search_idx');
            }

            if (Schema::hasColumn('users', 'idnumber')) {
                // idnumber is already unique in your schema, but the index helps LIKE queries
                $table->index('idnumber', 'users_idnumber_search_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_name_search_idx');
            $table->dropIndex('users_role_search_idx');
            $table->dropIndex('users_idnumber_search_idx');
        });
    }
};
