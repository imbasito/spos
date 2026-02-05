<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for FBR integration:
     * 1. fbr_pending_invoices - Queue for Store & Forward
     * 2. Adds fbr_invoice_id to orders table
     */
    public function up(): void
    {
        // Create pending invoices queue table
        Schema::create('fbr_pending_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->json('payload');
            $table->tinyInteger('attempts')->default(0);
            $table->string('last_error')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index(['attempts', 'created_at']);
        });

        // Add FBR fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fbr_invoice_id')->nullable()->after('status');
            $table->timestamp('fbr_synced_at')->nullable()->after('fbr_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fbr_pending_invoices');
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['fbr_invoice_id', 'fbr_synced_at']);
        });
    }
};
