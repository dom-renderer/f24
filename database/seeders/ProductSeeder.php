<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // $now = Carbon::now();
        // $unitId = \App\Models\OrderUnit::where('name', 'Kgs')->first(); // kg

        // if (!$unitId) {
        //     return false;
        // }

        // // Mapping Category Names to your category_id (Adjust IDs as needed)
        // $categoryMap = [
        //     'Basundi'      => 1,
        //     'Rabdi'        => 2,
        //     'Dairy Items'  => 3,
        //     'Specialities' => 4,
        // ];

        // $data = [
        //     "Basundi" => [
        //         ["name" => "White plain basudi", "wholesale" => 230, "mrp" => 420],
        //         ["name" => "Kesari Pista basudi", "wholesale" => 240, "mrp" => 440],
        //         ["name" => "Angoori basudi", "wholesale" => 240, "mrp" => 440],
        //         ["name" => "Orange Badam Basudi", "wholesale" => 240, "mrp" => 500],
        //         ["name" => "Rose Almond Basudi (New)", "wholesale" => 240, "mrp" => 500],
        //         ["name" => "Red Velvet basudi", "wholesale" => 240, "mrp" => 480],
        //         ["name" => "Caramel Roasted Almond Basundi", "wholesale" => 260, "mrp" => 440],
        //         ["name" => "Sitafal basudi", "wholesale" => 260, "mrp" => 440],
        //         ["name" => "Pinacruise basudi", "wholesale" => 260, "mrp" => 460],
        //         ["name" => "Pista Fusion basudi", "wholesale" => 260, "mrp" => 500],
        //         ["name" => "Choco brownie Basudi", "wholesale" => 260, "mrp" => 500],
        //         ["name" => "Anjeer Badam Basudi", "wholesale" => 260, "mrp" => 500],
        //         ["name" => "Royal Cream D/F Basudi (New)", "wholesale" => 280, "mrp" => 520],
        //         ["name" => "Tender Coconut Basudi (New)", "wholesale" => 280, "mrp" => 500],
        //         ["name" => "Choco Walnut Basudi", "wholesale" => 280, "mrp" => 500],
        //         ["name" => "Anjeer Walnut Basudi", "wholesale" => 280, "mrp" => 500],
        //         ["name" => "Madhu Malti Basudi", "wholesale" => 280, "mrp" => 500],
        //         ["name" => "Saffron Delight Basudi (New)", "wholesale" => 300, "mrp" => 520],
        //     ],
        //     "Rabdi" => [
        //         ["name" => "Kesari Pista Rabdi", "wholesale" => 400, "mrp" => 560],
        //         ["name" => "Angoori Rabdi", "wholesale" => 400, "mrp" => 560],
        //         ["name" => "Malai Rabdi", "wholesale" => 400, "mrp" => 560],
        //         ["name" => "Strawberry Rabdi", "wholesale" => 420, "mrp" => 600],
        //         ["name" => "Anjeer Walnut (New)", "wholesale" => 420, "mrp" => 600],
        //         ["name" => "Tender Coconut (New)", "wholesale" => 420, "mrp" => 600],
        //     ],
        //     "Dairy Items" => [
        //         ["name" => "Chaash loose ltr", "wholesale" => 60, "mrp" => 80],
        //         ["name" => "Dahi", "wholesale" => 85, "mrp" => 140],
        //         ["name" => "Paneer", "wholesale" => 360, "mrp" => 460],
        //         ["name" => "Masko", "wholesale" => 400, "mrp" => 600],
        //         ["name" => "Cream", "wholesale" => 400, "mrp" => 500],
        //         ["name" => "White halwo", "wholesale" => 480, "mrp" => 660],
        //         ["name" => "Makhan", "wholesale" => 600, "mrp" => 800],
        //     ],
        //     "Specialities" => [
        //         ["name" => "Mango Milkshake (Ras)", "wholesale" => 180, "mrp" => 300],
        //         ["name" => "Ras with Mango Tukda", "wholesale" => 220, "mrp" => 320],
        //         ["name" => "Cream Ras with Tukda", "wholesale" => 260, "mrp" => 360],
        //         ["name" => "Volcano Cruise Basudi (New)", "wholesale" => 280, "mrp" => 520],
        //         ["name" => "Apple Pie (New)", "wholesale" => 280, "mrp" => 520],
        //         ["name" => "Rasmadhuri", "wholesale" => 280, "mrp" => 460],
        //         ["name" => "Mango Pleasure", "wholesale" => 300, "mrp" => 460],
        //         ["name" => "Afghan Dryfruit Matho (New)", "wholesale" => 280, "mrp" => 460],
        //         ["name" => "Cream Fruit Salad", "wholesale" => 400, "mrp" => 560],
        //         ["name" => "Mango Cream", "wholesale" => 440, "mrp" => 600],
        //         ["name" => "Strawberry Cream", "wholesale" => 440, "mrp" => 600],
        //     ]
        // ];

        // foreach ($data as $categoryName => $products) {
        //     foreach ($products as $item) {
        //         $thisCategory = \App\Models\OrderCategory::updateOrCreate([
        //             'name' => $categoryName
        //         ])->id;

        //         // 1. Insert into order_products
        //         $productId = \App\Models\OrderProduct::updateOrCreate([
        //             'name'        => $item['name']
        //         ],[
        //             'category_id' => $thisCategory,
        //             'status'      => 1,
        //             'created_at'  => $now,
        //             'updated_at'  => $now,
        //         ])->id;

        //         // 2. Insert into order_product_units (linking to the product)
        //         \App\Models\OrderProductUnit::updateOrCreate([
        //             'order_product_id' => $productId,
        //             'unit_id'          => $unitId,
        //         ],[
        //             'price'            => $item['wholesale'],
        //             'status'           => 1,
        //             'created_at'       => $now,
        //             'updated_at'       => $now,
        //         ]);
        //     }
        // }

        // $data = [
        //     ["name" => "White plain basudi", "wholesale" => 230, "mrp" => 420],
        //     ["name" => "Kesari Pista basudi", "wholesale" => 240, "mrp" => 440],
        //     ["name" => "Angoori basudi", "wholesale" => 240, "mrp" => 440],
        //     ["name" => "Orange Badam Basudi", "wholesale" => 240, "mrp" => 500],
        //     ["name" => "Rose Almond Basudi (New)", "wholesale" => 240, "mrp" => 500],
        //     ["name" => "Red Velvet basudi", "wholesale" => 240, "mrp" => 480],
        //     ["name" => "Caramel Roasted Almond Basundi", "wholesale" => 260, "mrp" => 440],
        //     ["name" => "Sitafal basudi", "wholesale" => 260, "mrp" => 440],
        //     ["name" => "Pinacruise basudi", "wholesale" => 260, "mrp" => 460],
        //     ["name" => "Pista Fusion basudi", "wholesale" => 260, "mrp" => 500],
        //     ["name" => "Choco brownie Basudi", "wholesale" => 260, "mrp" => 500],
        //     ["name" => "Anjeer Badam Basudi", "wholesale" => 260, "mrp" => 500],
        //     ["name" => "Royal Cream D/F Basudi (New)", "wholesale" => 280, "mrp" => 520],
        //     ["name" => "Tender Coconut Basudi (New)", "wholesale" => 280, "mrp" => 500],
        //     ["name" => "Choco Walnut Basudi", "wholesale" => 280, "mrp" => 500],
        //     ["name" => "Anjeer Walnut Basudi", "wholesale" => 280, "mrp" => 500],
        //     ["name" => "Madhu Malti Basudi", "wholesale" => 280, "mrp" => 500],
        //     ["name" => "Saffron Delight Basudi (New)", "wholesale" => 300, "mrp" => 520],
        //     ["name" => "Kesari Pista Rabdi", "wholesale" => 400, "mrp" => 560],
        //     ["name" => "Angoori Rabdi", "wholesale" => 400, "mrp" => 560],
        //     ["name" => "Malai Rabdi", "wholesale" => 400, "mrp" => 560],
        //     ["name" => "Strawberry Rabdi", "wholesale" => 420, "mrp" => 600],
        //     ["name" => "Anjeer Walnut (New)", "wholesale" => 420, "mrp" => 600],
        //     ["name" => "Tender Coconut (New)", "wholesale" => 420, "mrp" => 600],
        //     ["name" => "Chaash loose ltr", "wholesale" => 60, "mrp" => 80],
        //     ["name" => "Dahi", "wholesale" => 85, "mrp" => 140],
        //     ["name" => "Paneer", "wholesale" => 360, "mrp" => 460],
        //     ["name" => "Masko", "wholesale" => 400, "mrp" => 600],
        //     ["name" => "Cream", "wholesale" => 400, "mrp" => 500],
        //     ["name" => "White halwo", "wholesale" => 480, "mrp" => 660],
        //     ["name" => "Makhan", "wholesale" => 600, "mrp" => 800],
        //     ["name" => "Mango Milkshake (Ras)", "wholesale" => 180, "mrp" => 300],
        //     ["name" => "Ras with Mango Tukda", "wholesale" => 220, "mrp" => 320],
        //     ["name" => "Cream Ras with Tukda", "wholesale" => 260, "mrp" => 360],
        //     ["name" => "Volcano Cruise Basudi (New)", "wholesale" => 280, "mrp" => 520],
        //     ["name" => "Apple Pie (New)", "wholesale" => 280, "mrp" => 520],
        //     ["name" => "Rasmadhuri", "wholesale" => 280, "mrp" => 460],
        //     ["name" => "Mango Pleasure", "wholesale" => 300, "mrp" => 460],
        //     ["name" => "Afghan Dryfruit Matho (New)", "wholesale" => 280, "mrp" => 460],
        //     ["name" => "Cream Fruit Salad", "wholesale" => 400, "mrp" => 560],
        //     ["name" => "Mango Cream", "wholesale" => 440, "mrp" => 600],
        //     ["name" => "Strawberry Cream", "wholesale" => 440, "mrp" => 600],
        // ];

        // $defaultTier = \App\Models\PricingTier::where('id', 2)->first()->id ?? null;

        // if ($defaultTier) {
        //     foreach (\App\Models\OrderProductUnit::where('id', '>=', 14)->orderBy('order_product_id', 'ASC')->get() as $index => $row) {
        //         if (isset($data[$index]['mrp'])) {
        //         \App\Models\UnitPriceTier::updateOrCreate([
        //             'pricing_tier_id' => $defaultTier,
        //             'product_id' => $row->order_product_id,
        //             'product_unit_id' => $row->unit_id
        //         ], [
        //             'amount' => $data[$index]['mrp'],
        //             'status' => 1
        //         ]);
        //         }
        //     }
        // }

        
    }
}