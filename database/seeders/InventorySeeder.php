<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Inventory::create([
            'product_id' => 'PROD-001',
            'name' => 'Laptop',
            'stock' => 100,
        ]);

        Inventory::create([
            'product_id' => 'PROD-002',
            'name' => 'Mouse',
            'stock' => 500,
        ]);

        Inventory::create([
            'product_id' => 'PROD-003',
            'name' => 'Keyboard',
            'stock' => 300,
        ]);
    }
}
