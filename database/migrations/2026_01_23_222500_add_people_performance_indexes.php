<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for People module performance
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!$this->hasIndex('suppliers', 'idx_suppliers_name')) {
                $table->index('name', 'idx_suppliers_name');
            }
            if (!$this->hasIndex('suppliers', 'idx_suppliers_phone')) {
                $table->index('phone', 'idx_suppliers_phone');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!$this->hasIndex('customers', 'idx_customers_name')) {
                $table->index('name', 'idx_customers_name');
            }
            // phone might already be indexed if it was unique, but let's be sure
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('idx_suppliers_name');
            $table->dropIndex('idx_suppliers_phone');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_name');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
};
