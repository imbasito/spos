<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * StartUpSeeder - Self-healing startup seeder with comprehensive safety checks
 * 
 * This seeder runs on EVERY application startup to ensure critical data exists.
 * It uses firstOrCreate pattern to avoid duplicates and provides self-healing
 * capabilities to recover from data corruption or missing records.
 * 
 * WHY ONLY THESE SEEDERS?
 * - UnitSeeder: Core measurement units (kg, liter, piece) - required for products
 * - CurrencySeeder: Currency system - required for all financial transactions
 * - RolePermissionSeeder: User roles & permissions - required for security/access control
 * 
 * These are the MINIMUM data required for the app to function. Other seeders like
 * ProductSeeder, CustomerSeeder are for demo/testing only and should NOT run on
 * every startup as they create duplicate test data.
 */
class StartUpSeeder extends Seeder
{
    /**
     * Run the database seeds with self-healing capabilities
     */
    public function run(): void
    {
        try {
            // Self-healing: Ensure admin user exists
            $user = $this->ensureAdminUser();
            
            // Self-healing: Ensure default customer exists
            $this->ensureWalkingCustomer();
            
            // Self-healing: Ensure default supplier exists
            $this->ensureOwnSupplier();
            
            // Self-healing: Ensure admin role and assignment
            $this->ensureAdminRole($user);
            
            // Critical seeders: Units, Currency, Permissions
            // These use firstOrCreate internally, so safe to call repeatedly
            $this->call([
                UnitSeeder::class,        // Measurement units (kg, liter, etc.)
                CurrencySeeder::class,    // Currency system (PKR, USD, etc.)
                RolePermissionSeeder::class, // Roles & Permissions (Admin, Cashier, etc.)
            ]);
            
            Log::info('StartUpSeeder completed successfully with self-healing checks');
            
        } catch (\Exception $e) {
            Log::error('StartUpSeeder failed: ' . $e->getMessage());
            // Don't throw - allow app to continue even if seeding partially fails
        }
    }
    
    /**
     * Self-healing: Ensure admin user exists
     */
    private function ensureAdminUser()
    {
        try {
            return User::firstOrCreate(
                ['email' => 'admin@admin.com'],
                [
                    'name' => 'Administrator',
                    'password' => bcrypt('12345678'),
                    'username' => 'admin'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to create admin user: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Self-healing: Ensure walking customer exists
     */
    private function ensureWalkingCustomer()
    {
        try {
            Customer::firstOrCreate(
                ['phone' => '0000000000'],
                ['name' => 'Walking Customer']
            );
        } catch (\Exception $e) {
            Log::error('Failed to create walking customer: ' . $e->getMessage());
        }
    }
    
    /**
     * Self-healing: Ensure own supplier exists
     */
    private function ensureOwnSupplier()
    {
        try {
            Supplier::firstOrCreate(
                ['phone' => '0000000000'],
                ['name' => 'Own Supplier']
            );
        } catch (\Exception $e) {
            Log::error('Failed to create own supplier: ' . $e->getMessage());
        }
    }
    
    /**
     * Self-healing: Ensure admin role exists and is assigned
     */
    private function ensureAdminRole($user)
    {
        try {
            if (!$user) return;
            
            $role = Role::firstOrCreate(['name' => 'Admin']);
            
            // Ensure user has admin role
            if (!$user->hasRole('Admin')) {
                $user->syncRoles($role);
            }
        } catch (\Exception $e) {
            Log::error('Failed to ensure admin role: ' . $e->getMessage());
        }
    }
}
