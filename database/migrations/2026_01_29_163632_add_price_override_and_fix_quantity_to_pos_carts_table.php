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
        Schema::table('pos_carts', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->default(1)->change();
            $table->decimal('price_override', 12, 2)->nullable()->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_carts', function (Blueprint $table) {
            $table->dropColumn('price_override');
            $table->integer('quantity')->default(1)->change();
        });
    }
};
