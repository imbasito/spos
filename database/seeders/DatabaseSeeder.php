<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * PRODUCTION SEEDER CHAIN:
     *   StartUpSeeder         → Admin user, Walking Customer, Own Supplier, basic roles
     *     └─ UnitSeeder       → Default units (KG, Piece, Litre, etc.)
     *     └─ CurrencySeeder   → PKR + other currencies
     *     └─ RolePermissionSeeder → All permissions, Cashier + Sales users & roles
     *
     * DEMO / DEVELOPMENT SEEDERS (run manually, never in production chain):
     *   ProductionSeeder      → Full production-ready data set
     *   ProductSeeder         → Sample products
     *   CustomerSeeder        → Sample customers
     *   SupplierSeeder        → Sample suppliers
     *   PurchaseSeeder        → Sample purchases
     *   LargeScaleDataSeeder  → High-volume stress-test data (dev only)
     *   SweetShopProductSeeder→ Sweet-shop domain products (dev only)
     *   ImageLinkSeeder       → Link images to existing products (dev only)
     *
     * DO NOT enable RefundPermissionSeeder — it creates a 'Cashier' role (uppercase)
     * separate from the 'cashier' role (lowercase) already created by RolePermissionSeeder.
     */
    public function run(): void
    {
        $this->call([
            StartUpSeeder::class,
        ]);
    }
}
