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
        Schema::table('orders', function (Blueprint $table) {
            // Tax rate at time of sale (e.g., 17.00 for 17%)
            $table->decimal('tax_rate', 5, 2)->default(0)->after('discount');
            // Calculated tax amount at time of sale
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'tax_amount']);
        });
    }
};
