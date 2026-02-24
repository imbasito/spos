<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for Brand and Category searching
     */
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (!$this->hasIndex('brands', 'brands_name_index')) {
                $table->index('name', 'brands_name_index');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (!$this->hasIndex('categories', 'categories_name_index')) {
                $table->index('name', 'categories_name_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('brands_name_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_name_index');
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
