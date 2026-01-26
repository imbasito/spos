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
        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('opening_amount', 15, 2)->default(0);
            $table->decimal('cash_in_hand', 15, 2)->nullable(); // Actual Count
            $table->decimal('system_cash', 15, 2)->nullable(); // Expected
            $table->decimal('difference', 15, 2)->nullable();
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_returns', 15, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->timestamp('closed_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
