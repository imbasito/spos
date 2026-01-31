<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance optimization: Add indexes to frequently queried columns
 * This improves query performance for common operations like:
 * - Fetching orders by customer or user
 * - Looking up order products by product or order
 * - Finding carts by user
 * - Filtering products by category/brand
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            // Index for customer lookups (order history, customer reports)
            if (!$this->hasIndex('orders', 'orders_customer_id_index')) {
                $table->index('customer_id', 'orders_customer_id_index');
            }
            // Index for user lookups (cashier reports, user activity)
            if (!$this->hasIndex('orders', 'orders_user_id_index')) {
                $table->index('user_id', 'orders_user_id_index');
            }
            // Index for date-based queries (daily reports, sales analytics)
            if (!$this->hasIndex('orders', 'orders_created_at_index')) {
                $table->index('created_at', 'orders_created_at_index');
            }
        });

        // Order products table indexes
        Schema::table('order_products', function (Blueprint $table) {
            // Index for product sales reports
            if (!$this->hasIndex('order_products', 'order_products_product_id_index')) {
                $table->index('product_id', 'order_products_product_id_index');
            }
            // Index for order detail lookups
            if (!$this->hasIndex('order_products', 'order_products_order_id_index')) {
                $table->index('order_id', 'order_products_order_id_index');
            }
        });

        // POS carts table indexes
        Schema::table('pos_carts', function (Blueprint $table) {
            // Index for user cart lookups
            if (!$this->hasIndex('pos_carts', 'pos_carts_user_id_index')) {
                $table->index('user_id', 'pos_carts_user_id_index');
            }
            // Index for product lookups in cart
            if (!$this->hasIndex('pos_carts', 'pos_carts_product_id_index')) {
                $table->index('product_id', 'pos_carts_product_id_index');
            }
        });

        // Products table indexes (for common filters)
        Schema::table('products', function (Blueprint $table) {
            // Index for category filtering
            if (!$this->hasIndex('products', 'products_category_id_index')) {
                $table->index('category_id', 'products_category_id_index');
            }
            // Index for brand filtering
            if (!$this->hasIndex('products', 'products_brand_id_index')) {
                $table->index('brand_id', 'products_brand_id_index');
            }
            // Index for barcode lookups (POS scanning)
            if (!$this->hasIndex('products', 'products_barcode_index')) {
                $table->index('barcode', 'products_barcode_index');
            }
            // Index for SKU lookups
            if (!$this->hasIndex('products', 'products_sku_index')) {
                $table->index('sku', 'products_sku_index');
            }
        });

        // Purchases table indexes
        Schema::table('purchases', function (Blueprint $table) {
            // Index for supplier reports
            if (!$this->hasIndex('purchases', 'purchases_supplier_id_index')) {
                $table->index('supplier_id', 'purchases_supplier_id_index');
            }
            // Index for user activity
            if (!$this->hasIndex('purchases', 'purchases_user_id_index')) {
                $table->index('user_id', 'purchases_user_id_index');
            }
        });

        // Activity logs table indexes
        Schema::table('activity_logs', function (Blueprint $table) {
            // Index for user activity filtering
            if (!$this->hasIndex('activity_logs', 'activity_logs_user_id_index')) {
                $table->index('user_id', 'activity_logs_user_id_index');
            }
            // Index for date-based log queries
            if (!$this->hasIndex('activity_logs', 'activity_logs_created_at_index')) {
                $table->index('created_at', 'activity_logs_created_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_customer_id_index');
            $table->dropIndex('orders_user_id_index');
            $table->dropIndex('orders_created_at_index');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropIndex('order_products_product_id_index');
            $table->dropIndex('order_products_order_id_index');
        });

        Schema::table('pos_carts', function (Blueprint $table) {
            $table->dropIndex('pos_carts_user_id_index');
            $table->dropIndex('pos_carts_product_id_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_id_index');
            $table->dropIndex('products_brand_id_index');
            $table->dropIndex('products_barcode_index');
            $table->dropIndex('products_sku_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_supplier_id_index');
            $table->dropIndex('purchases_user_id_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_user_id_index');
            $table->dropIndex('activity_logs_created_at_index');
        });
    }

    /**
     * Check if an index exists on a table
     */
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
