<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImageLinkSeeder extends Seeder
{
    public function run()
    {
        // map filname 'slug' part to product name likely
        // Files: barfi_mix.png, gulab_jamun.jpg, jalebi.png, etc.
        
        $map = [
            'gulab_jamun' => 'Gulab Jamun',
            'barfi_mix' => 'Barfi', 
            'jalebi' => 'Jalebi', # Need to create if not exists or check
            'kaju_katli_250g' => 'Kaju Katli', # Standardize
            'kalakand' => 'Kalakand',
            'laddu_besan' => 'Laddu',
            'milk_cake' => 'Milk Cake',
            'peda' => 'Peda',
            'rasgulla' => 'Rasgulla',
            'soan_papdi' => 'Soan Papdi'
        ];

        foreach ($map as $fileSlug => $productName) {
            // Find ext
            $ext = 'png';
            if ($fileSlug == 'gulab_jamun') $ext = 'jpg';
            
            $file = "assets/images/pos/{$fileSlug}.{$ext}";

            // Update product image if product exists
            DB::table('products')
                ->where('name', 'LIKE', "%{$productName}%")
                ->update(['image' => $file]);
                
            $this->command->info("Linked $file to $productName");
        }
    }
}
