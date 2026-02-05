<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LargeScaleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Increase memory limit for large seeding
        ini_set('memory_limit', '512M');
        $faker = \Faker\Factory::create();

        $this->command->info('Starting Large Scale Data Seeding...');

        // 1. Create Base Data (Units, Categories, Brands)
        $unitId = \Illuminate\Support\Facades\DB::table('units')->insertGetId([
            'title' => 'Piece', 'short_name' => 'pc', 'created_at' => now(), 'updated_at' => now()
        ]);
        
        $catIds = [];
        for ($i = 0; $i < 10; $i++) {
            $catIds[] = \Illuminate\Support\Facades\DB::table('categories')->insertGetId([
                'name' => ucfirst($faker->word), 'status' => 1, 'created_at' => now(), 'updated_at' => now()
            ]);
        }

        $brandIds = [];
        for ($i = 0; $i < 5; $i++) {
            $brandIds[] = \Illuminate\Support\Facades\DB::table('brands')->insertGetId([
                'name' => $faker->company, 'status' => 1, 'created_at' => now(), 'updated_at' => now()
            ]);
        }

        $this->command->info('Base data created.');

        // 2. Create 100 Products
        $productIds = [];
        $productsData = [];
        
        for ($i = 0; $i < 100; $i++) {
            $price = $faker->numberBetween(100, 5000);
            $purchasePrice = $price * 0.7; // 30% margin
            
            $productsData[] = [
                'name' => implode(" ", $faker->words(3)),
                'slug' => $faker->slug . '-' . $i,
                'sku' => $faker->unique()->ean13,
                'category_id' => $faker->randomElement($catIds),
                'brand_id' => $faker->randomElement($brandIds),
                'unit_id' => $unitId,
                'price' => $price,
                'purchase_price' => $purchasePrice,
                'quantity' => 1000, // Stock plenty
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Chunk insert products
        foreach (array_chunk($productsData, 50) as $chunk) {
            \Illuminate\Support\Facades\DB::table('products')->insert($chunk);
        }
        
        // Retrieve IDs and prices for order generation
        $products = \Illuminate\Support\Facades\DB::table('products')->select('id', 'price', 'purchase_price')->get();
        $this->command->info('100 Products created.');

        // 3. Create Customers
        $customerIds = [];
        for ($i = 0; $i < 10; $i++) {
            $customerIds[] = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
                'name' => $faker->name,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 4. Create 10,000 Orders (Chunked)
        $this->command->info('Generating 10,000 Orders...');
        
        $batchSize = 500;
        $totalOrders = 10000;
        $adminId = 1; // Assuming admin ID is 1

        for ($batch = 0; $batch < $totalOrders / $batchSize; $batch++) {
            $ordersBuffer = [];
            $orderItemsBuffer = [];
            $transactionsBuffer = [];
            
            // We need order IDs, so we must insert individually or use a trick.
            // Using insertGetId in a loop for 10k is OK, but slow.
            // Better: Insert orders, then get the IDs.
            // To match them up, we can use a temporary valid-ish assumption ID range if single threaded,
            // OR just insert one by one. For 10k, one by one is acceptable (~1-2 mins).
            // Let's do one by one for safety and relationship integrity.
            
            for ($i = 0; $i < $batchSize; $i++) {
                $customerId = $faker->randomElement($customerIds);
                $orderDate = $faker->dateTimeBetween('-1 year', 'now');
                
                // Pick 1-5 items
                $itemCount = rand(1, 5);
                $selectedProducts = $products->random($itemCount);
                
                $itemsTotal = 0;
                $lineItems = [];
                
                foreach ($selectedProducts as $prod) {
                    $qty = rand(1, 5);
                    $lineTotal = $prod->price * $qty;
                    $itemsTotal += $lineTotal;
                    
                    $lineItems[] = [
                        'product_id' => $prod->id,
                        'quantity' => $qty,
                        'price' => $prod->price,
                        'purchase_price' => $prod->purchase_price,
                        'sub_total' => $lineTotal,
                        'discount' => 0,
                        'total' => $lineTotal,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ];
                }
                
                $orderId = \Illuminate\Support\Facades\DB::table('orders')->insertGetId([
                    'customer_id' => $customerId,
                    'user_id' => $adminId,
                    'sub_total' => $itemsTotal,
                    'discount' => 0,
                    'total' => $itemsTotal,
                    'paid' => $itemsTotal, // Fully paid
                    'due' => 0,
                    'status' => 1, // Paid
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);
                
                // Attach Order ID to items and buffer
                foreach ($lineItems as &$item) {
                    $item['order_id'] = $orderId;
                    $orderItemsBuffer[] = $item;
                }
                
                // Transaction
                $transactionsBuffer[] = [
                    'order_id' => $orderId,
                    'amount' => $itemsTotal,
                    'customer_id' => $customerId,
                    'user_id' => $adminId,
                    'paid_by' => 'cash',
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ];
            }
            
            // Bulk insert items and transactions
            \Illuminate\Support\Facades\DB::table('order_products')->insert($orderItemsBuffer);
            \Illuminate\Support\Facades\DB::table('order_transactions')->insert($transactionsBuffer);
            
            $this->command->info("Batch " . ($batch + 1) . " completed (" . ($batch + 1) * $batchSize . " orders)");
        }
        
        $this->command->info('Seeding Completed Successfully!');
    }
}
