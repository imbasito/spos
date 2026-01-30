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
             $table->decimal('row_total_override', 12, 2)->nullable()->after('price_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_carts', function (Blueprint $table) {
            $table->dropColumn('row_total_override');
        });
    }
};
