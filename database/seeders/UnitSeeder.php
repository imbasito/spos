<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['title' => 'Piece', 'short_name' => 'pcs'],
            ['title' => 'Kilogram', 'short_name' => 'kg'],
            ['title' => 'Liter', 'short_name' => 'L'],
            ['title' => 'Meter', 'short_name' => 'm'],
            ['title' => 'Dozen', 'short_name' => 'dz'],
            ['title' => 'Box', 'short_name' => 'box'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['short_name' => $unit['short_name']],
                ['title' => $unit['title']]
            );
        }
    }
}
