<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop duplicate barcode + SKU indexes from the products table.
 *
 * WHY:
 *   Migration 2026_01_14 added:  idx_products_barcode (on barcode column)
 *   Migration 2026_01_23 added:  idx_products_sku     (on sku column)
 *   Migration 2026_01_30 added:  products_barcode_index (same barcode column, different name → DUPLICATE)
 *                                 products_sku_index     (same sku column, different name → DUPLICATE)
 *
 *   Because 2026_01_30 used different index names, MySQL created separate indexes on the
 *   same columns. Every INSERT and UPDATE on the products table was maintaining 4 index
 *   B-trees instead of 2 — wasteful on every price edit, stock adjustment, and POS scan.
 *
 * WHAT IS KEPT:
 *   idx_products_barcode  — the original, correctly named barcode index
 *   idx_products_sku      — the original, correctly named SKU index
 *
 * WHAT IS DROPPED:
 *   products_barcode_index — redundant duplicate
 *   products_sku_index     — redundant duplicate
 *
 * SAFETY:
 *   - Uses SHOW INDEX to verify existence before every DROP (idempotent)
 *   - Will not fail on a fresh install or if already applied
 *   - Down() re-adds with the same existence check (safe rollback)
 *   - Zero data changes — indexes only affect query/write performance
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop duplicate barcode index (keep idx_products_barcode)
        if ($this->indexExists('products', 'products_barcode_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_barcode_index');
            });
        }

        // Drop duplicate SKU index (keep idx_products_sku)
        if ($this->indexExists('products', 'products_sku_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_sku_index');
            });
        }
    }

    public function down(): void
    {
        // Restore products_barcode_index if it was dropped
        if (!$this->indexExists('products', 'products_barcode_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('barcode', 'products_barcode_index');
            });
        }

        // Restore products_sku_index if it was dropped
        if (!$this->indexExists('products', 'products_sku_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('sku', 'products_sku_index');
            });
        }
    }

    /**
     * Check whether a named index exists using SHOW INDEX (MySQL-specific, reliable).
     * Uses a parameterised query to avoid SQL injection.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $results = DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        return count($results) > 0;
    }
};
