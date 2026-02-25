<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Composite index for the most common filtered queries:
            // filtering by action + module + date range (ORDER BY created_at DESC)
            if (!$this->hasIndex('activity_logs', 'activity_logs_action_module_date_idx')) {
                $table->index(
                    ['action', 'module', 'created_at'],
                    'activity_logs_action_module_date_idx'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_action_module_date_idx');
        });
    }

    /**
     * Check if a named index already exists (defensive for legacy restores).
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        return count($indexes) > 0;
    }
};
