<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StartUpSeeder::class,
            // Demo data seeders - commented out for production
            // ProductSeeder::class,
            // CustomerSeeder::class,
            // SupplierSeeder::class,
            // PurchaseSeeder::class,
            // RefundPermissionSeeder::class,
        ]);
    }
}
