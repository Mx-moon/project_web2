<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Location::create(['name' => 'FiNALi Warehouse', 'type' => 'warehouse', 'address' => 'Address to be added']);
        $show1     = Location::create(['name' => 'Kasikas Showroom 1', 'type' => 'showroom', 'address' => 'Address to be added']);
        $show2     = Location::create(['name' => 'Kasikas Showroom 2', 'type' => 'showroom', 'address' => 'Address to be added']);

        // [SKU, name, category, dimensions, price, stock at warehouse / showroom 1 / showroom 2]
        $products = [
            ['SF-VL-01', 'Modern Velvet Sofa', 'Seating', '220x95x85 cm', 1200, [14, 0, 0]],
            ['DT-OK-06', 'Oak Dining Table (Set of 6)', 'Dining Table', '180x90x75 cm', 850, [8, 2, 0]],
            ['LC-SC-03', 'Scandi Lounge Chair', 'Seating', '85x80x90 cm', 450, [22, 1, 0]],
            ['SB-OAK-08', 'Mid-Century Sideboard', 'Storage', '160x45x75 cm', 950, [2, 0, 0]],
            ['BF-WN-02', 'Minimalist Bed Frame', 'Bedroom', '210x160x100 cm', 1100, [1, 0, 0]],
        ];

        foreach ($products as [$sku, $name, $category, $size, $price, $qty]) {
            $product = Product::create([
                'sku' => $sku, 'name' => $name, 'category' => $category,
                'dimensions' => $size, 'price' => $price,
            ]);

            foreach ([$warehouse, $show1, $show2] as $i => $location) {
                Stock::create([
                    'product_id'  => $product->id,
                    'location_id' => $location->id,
                    'quantity'    => $qty[$i],
                ]);
            }
        }

        // Demo accounts. Change these passwords before real use.
        User::create(['name' => 'Maria Lopez', 'username' => 'kasikas', 'email' => 'kasikas@example.com', 'password' => Hash::make('password'), 'role' => 'kasikas', 'location_id' => $show1->id]);
        User::create(['name' => 'Jose Reyes', 'username' => 'finali', 'email' => 'finali@example.com', 'password' => Hash::make('password'), 'role' => 'finali', 'location_id' => $warehouse->id]);
        User::create(['name' => 'Admin User', 'username' => 'admin', 'email' => 'admin@example.com', 'password' => Hash::make('password'), 'role' => 'admin']);
    }
}