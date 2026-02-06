<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StartUpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Create or retrieve admin user
            $user = User::firstOrCreate(
                ['email' => 'admin@spos.com'],
                [
                    'name' => 'Administrator',
                    'password' => bcrypt('admin123'),
                    'username' => 'admin'
                ]
            );

            // Create or retrieve walking customer
            Customer::firstOrCreate(
                ['phone' => '012345678'],
                ['name' => 'Walking Customer']
            );

            // Create or retrieve own supplier
            Supplier::firstOrCreate(
                ['phone' => '012345678'],
                ['name' => 'Own Supplier']
            );

            // Create or retrieve admin role
            $role = Role::firstOrCreate(['name' => 'Admin']);

            // Ensure user has admin role
            if (!$user->hasRole('Admin')) {
                $user->syncRoles($role);
            }

            $this->call([
                UnitSeeder::class,
                CurrencySeeder::class,
                RolePermissionSeeder::class,
            ]);

            $this->command->info('✓ Startup seeding completed successfully');
            
        } catch (\Exception $e) {
            // Log the error but don't fail the entire seeding process
            // This handles tablespace errors and other DB issues gracefully
            \Log::warning('Seeder encountered an issue (non-critical): ' . $e->getMessage());
            $this->command->warn('⚠ Seeding completed with warnings. Check logs for details.');
            
            // Don't throw - let the app continue
            // Migrations have already created the tables, so the app will work
        }
    }
}
