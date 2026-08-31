<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mock_boards', function (Blueprint $table) {
            if (! Schema::hasColumn('mock_boards', 'status')) {
                $table->string('status')->default('pending')->after('program');
            }
            if (! Schema::hasColumn('mock_boards', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('mock_boards', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('mock_boards', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mock_boards', function (Blueprint $table) {
            if (Schema::hasColumn('mock_boards', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            $table->dropColumn(array_filter([
                Schema::hasColumn('mock_boards', 'status') ? 'status' : null,
                Schema::hasColumn('mock_boards', 'approved_at') ? 'approved_at' : null,
                Schema::hasColumn('mock_boards', 'rejection_reason') ? 'rejection_reason' : null,
            ]));
        });
    }
};
