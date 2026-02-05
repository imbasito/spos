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
        Schema::table('products', function (Blueprint $table) {
            $table->string('urdu_name')->nullable()->after('name')->comment('Product name in Urdu');
            $table->string('hs_code', 20)->nullable()->after('sku')->comment('Harmonized System code for customs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['urdu_name', 'hs_code']);
        });
    }
};
