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
        // Change quantity to double in products table
        Schema::table('products', function (Blueprint $table) {
            $table->double('quantity')->default(0)->change();
        });

        // Change quantity to double in pos_carts table
        Schema::table('pos_carts', function (Blueprint $table) {
            $table->double('quantity')->default(1)->change();
        });

        // Change quantity to double in order_products table
        Schema::table('order_products', function (Blueprint $table) {
            $table->double('quantity')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });

        Schema::table('pos_carts', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->change();
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->change();
        });
    }
};
