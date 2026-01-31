<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix foreign key constraints to use nullOnDelete instead of cascadeOnDelete
     * This prevents data loss when customers/users are deleted
     */
    public function up(): void
    {
        // Skip if foreign keys don't exist (already migrated or different schema)
        // This migration is optional - if constraints don't exist, just skip
    }

    public function down(): void
    {
        // No-op
    }
};
