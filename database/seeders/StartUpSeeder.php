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
    }
}
