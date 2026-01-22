<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RefundPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'refund_view',
            'refund_create',
            'refund_delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to Admin role
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $role->givePermissionTo($permissions);
        
        // Assign to Cashier role (optional, usually cashiers can create refunds)
        $cashier = Role::firstOrCreate(['name' => 'Cashier']);
        $cashier->givePermissionTo(['refund_create', 'refund_view']);
    }
}
