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
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('grand_total')->comment('Amount paid to supplier');
            $table->string('payment_status', 20)->default('unpaid')->after('paid_amount')->comment('unpaid, partial, paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_status']);
        });
    }
};
