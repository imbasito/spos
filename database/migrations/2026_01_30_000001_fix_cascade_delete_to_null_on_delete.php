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
        // Fix orders table - change customer_id and user_id to nullOnDelete
        Schema::table('orders', function (Blueprint $table) {
            // Drop existing foreign keys first
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('orders', function (Blueprint $table) {
            // Re-add with nullOnDelete
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
