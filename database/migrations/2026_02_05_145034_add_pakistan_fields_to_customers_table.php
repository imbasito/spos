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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('cnic', 15)->nullable()->after('phone')->comment('CNIC for FBR compliance (xxxxx-xxxxxxx-x)');
            $table->decimal('credit_limit', 12, 2)->default(0)->after('address')->comment('Max allowed credit balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['cnic', 'credit_limit']);
        });
    }
};
