<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barcode_history', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('label');
            $table->string('label_size', 20)->default('large')->after('price');
            $table->date('mfg_date')->nullable()->after('label_size');
            $table->date('exp_date')->nullable()->after('mfg_date');
            $table->boolean('show_price')->default(false)->after('exp_date');
        });
    }

    public function down(): void
    {
        Schema::table('barcode_history', function (Blueprint $table) {
            $table->dropColumn(['price', 'label_size', 'mfg_date', 'exp_date', 'show_price']);
        });
    }
};
