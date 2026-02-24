<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CatalogSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $categories = [
            ['name' => 'Lassi', 'description' => 'Refreshing yogurt based drinks', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ice Cream & Kulfi', 'description' => 'Creamy desserts and kulfi delights', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rabdi & Faluda', 'description' => 'Traditional milk desserts & faluda', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($categories as $cat) {
            \App\Models\OrderCategory::firstOrCreate(
                ['name' => $cat['name']],
                $cat
            );
        }

        $units = [
            ['name' => '250 ml Glass'],
            ['name' => '500 ml Glass'],
            ['name' => '1 Scoop'],
            ['name' => '2 Scoop'],
            ['name' => '500 ml Tub'],
            ['name' => '1 Litre Tub'],
            ['name' => 'Stick (Single)'],
            ['name' => 'Box (Pack of 10)'],
            ['name' => '250 gm'],
            ['name' => '500 gm'],
            ['name' => 'Regular Glass'],
            ['name' => 'Large Glass'],
        ];

        foreach ($units as $unit) {
            \App\Models\OrderUnit::firstOrCreate(
                ['name' => $unit['name']],
                [
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $lassiId = DB::table('order_categories')->where('name', 'Lassi')->value('id');
        $iceCreamId = DB::table('order_categories')->where('name', 'Ice Cream & Kulfi')->value('id');
        $rabdiId = DB::table('order_categories')->where('name', 'Rabdi & Faluda')->value('id');

        $products = [
            ['name' => 'Rajwadi Lassi', 'category_id' => $lassiId, 'sku' => 'LASSI-001', 'created_at' => $now],
            ['name' => 'Blueberry Lassi', 'category_id' => $lassiId, 'sku' => 'LASSI-002', 'created_at' => $now],
            ['name' => 'Butterscotch Lassi', 'category_id' => $lassiId, 'sku' => 'LASSI-003', 'created_at' => $now],
            ['name' => 'Mango Lassi', 'category_id' => $lassiId, 'sku' => 'LASSI-004', 'created_at' => $now],

            ['name' => 'American Nuts Ice Cream', 'category_id' => $iceCreamId, 'sku' => 'ICE-001', 'created_at' => $now],
            ['name' => 'Butterscotch Ice Cream', 'category_id' => $iceCreamId, 'sku' => 'ICE-002', 'created_at' => $now],
            ['name' => 'Kesari Pista Kulfi', 'category_id' => $iceCreamId, 'sku' => 'ICE-003', 'created_at' => $now],
            ['name' => 'Malai Rocket Kulfi', 'category_id' => $iceCreamId, 'sku' => 'ICE-004', 'created_at' => $now],

            ['name' => 'Kesari Pista Rabdi', 'category_id' => $rabdiId, 'sku' => 'RAD-001', 'created_at' => $now],
            ['name' => 'Angoori Rabdi', 'category_id' => $rabdiId, 'sku' => 'RAD-002', 'created_at' => $now],
            ['name' => 'Faluda Kulfi Mix', 'category_id' => $rabdiId, 'sku' => 'RAD-003', 'created_at' => $now],
            ['name' => 'Rose Faluda', 'category_id' => $rabdiId, 'sku' => 'RAD-004', 'created_at' => $now],
        ];

        foreach ($products as $prod) {
            \App\Models\OrderProduct::firstOrCreate(
                ['sku' => $prod['sku']],
                [
                    'name' => $prod['name'],
                    'category_id' => $prod['category_id'],
                    'status' => 1,
                    'description' => null,
                    'created_at' => $prod['created_at'],
                    'updated_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ATTACH REALISTIC UNITS TO PRODUCTS
        |--------------------------------------------------------------------------
        */

        $unitIds = DB::table('order_units')->pluck('id', 'name');

        $products = \App\Models\OrderProduct::with('category')->get();

        foreach ($products as $product) {

            $categoryName = $product->category->name;

            if ($categoryName === 'Lassi') {

                // 250 ml
                \App\Models\OrderProductUnit::firstOrCreate(
                    [
                        'order_product_id' => $product->id,
                        'unit_id' => $unitIds['250 ml Glass'],
                    ],
                    [
                        'price' => 60,
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                // 500 ml
                \App\Models\OrderProductUnit::firstOrCreate(
                    [
                        'order_product_id' => $product->id,
                        'unit_id' => $unitIds['500 ml Glass'],
                    ],
                    [
                        'price' => 100,
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            elseif ($categoryName === 'Ice Cream & Kulfi') {

                // 1 Scoop
                \App\Models\OrderProductUnit::firstOrCreate(
                    [
                        'order_product_id' => $product->id,
                        'unit_id' => $unitIds['1 Scoop'],
                    ],
                    [
                        'price' => 40,
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                // 2 Scoop
                \App\Models\OrderProductUnit::firstOrCreate(
                    [
                        'order_product_id' => $product->id,
                        'unit_id' => $unitIds['2 Scoop'],
                    ],
                    [
                        'price' => 70,
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                // 1 Litre Tub
                \App\Models\OrderProductUnit::firstOrCreate(
                    [
                        'order_product_id' => $product->id,
                        'unit_id' => $unitIds['1 Litre Tub'],
                    ],
                    [
                        'price' => 320,
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            elseif ($categoryName === 'Rabdi & Faluda') {

                // 250 gm
                \App\Models\OrderProductUnit::firstOrCreate(
                    [
                        'order_product_id' => $product->id,
                        'unit_id' => $unitIds['250 gm'],
                    ],
                    [
                        'price' => 90,
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                // 500 gm
                \App\Models\OrderProductUnit::firstOrCreate(
                    [
                        'order_product_id' => $product->id,
                        'unit_id' => $unitIds['500 gm'],
                    ],
                    [
                        'price' => 160,
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | TAX SLABS (Indian GST)
        |--------------------------------------------------------------------------
        */
        $taxSlabs = [
            ['name' => 'GST 0%',  'cgst' => 0,  'sgst' => 0,  'igst' => 0],
            ['name' => 'GST 5%',  'cgst' => 2.5,'sgst' => 2.5,'igst' => 5],
            ['name' => 'GST 12%', 'cgst' => 6,  'sgst' => 6,  'igst' => 12],
            ['name' => 'GST 18%', 'cgst' => 9,  'sgst' => 9,  'igst' => 18],
        ];

        foreach ($taxSlabs as $slab) {
            \App\Models\TaxSlab::firstOrCreate(
                ['name' => $slab['name']],
                array_merge($slab, [
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $gst5  = DB::table('tax_slabs')->where('name', 'GST 5%')->value('id');
        $gst18 = DB::table('tax_slabs')->where('name', 'GST 18%')->value('id');

        /*
        |--------------------------------------------------------------------------
        | PACKAGING MATERIALS
        |--------------------------------------------------------------------------
        */
        $packaging = [
            ['name' => 'Ice Cream Cup (100ml)', 'price_per_piece' => 3.00, 'tax_slab_id' => $gst5],
            ['name' => 'Ice Cream Box (1L)', 'price_per_piece' => 12.00, 'tax_slab_id' => $gst5],
            ['name' => 'Thermocol Box (Large)', 'price_per_piece' => 80.00, 'tax_slab_id' => $gst18],
            ['name' => 'Plastic Spoon', 'price_per_piece' => 1.00, 'tax_slab_id' => $gst5],
        ];

        foreach ($packaging as $item) {
            \App\Models\PackagingMaterial::firstOrCreate(
                ['name' => $item['name']],
                [
                    'pricing_type' => 'fixed',
                    'price_per_piece' => $item['price_per_piece'],
                    'price_includes_tax' => 0,
                    'tax_slab_id' => $item['tax_slab_id'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | OTHER ITEMS (Extra Charges)
        |--------------------------------------------------------------------------
        */
        $otherItems = [
            ['name' => 'Delivery Charge', 'pricing_type' => 'fixed', 'price' => 50, 'tax' => $gst18],
            ['name' => 'Dry Ice Charge', 'pricing_type' => 'fixed', 'price' => 100, 'tax' => $gst5],
            ['name' => 'Decoration Setup', 'pricing_type' => 'as_per_actual', 'price' => null, 'tax' => $gst18],
            ['name' => 'Extra Staff Service', 'pricing_type' => 'as_per_actual', 'price' => null, 'tax' => $gst18],
        ];

        foreach ($otherItems as $item) {
            \App\Models\OtherItem::firstOrCreate(
                ['name' => $item['name']],
                [
                    'pricing_type' => $item['pricing_type'],
                    'price_per_piece' => $item['price'],
                    'price_includes_tax' => 0,
                    'tax_slab_id' => $item['tax'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UTENSILS (Catering Use)
        |--------------------------------------------------------------------------
        */
        $utencils = [
            'Ice Cream Scooper',
            'Faluda Glass',
            'Steel Serving Spoon',
            'Milk Can (40L)',
            'Insulated Ice Cream Tub',
        ];

        foreach ($utencils as $name) {
            \App\Models\Utencil::firstOrCreate(
                ['name' => $name],
                [
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $tiers = [
            'Regular',
            'Wholesale',
            'VIP Catering'
        ];

        foreach ($tiers as $tier) {
            \App\Models\PricingTier::firstOrCreate([
                'name' => $tier,
                'slug' => \Illuminate\Support\Str::slug($tier)
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PACKAGING MATERIALS
        |--------------------------------------------------------------------------
        */

        $packagingMaterials = [
            ['name' => 'Ice Cream Cup (100 ml)', 'price' => 3.00, 'tax' => $gst5],
            ['name' => 'Ice Cream Cup (250 ml)', 'price' => 5.00, 'tax' => $gst5],
            ['name' => '500 ml Ice Cream Box', 'price' => 10.00, 'tax' => $gst5],
            ['name' => '1 Litre Ice Cream Box', 'price' => 18.00, 'tax' => $gst5],
            ['name' => 'Kulfi Stick Wrapper', 'price' => 1.50, 'tax' => $gst5],
            ['name' => 'Thermocol Box (Large)', 'price' => 80.00, 'tax' => $gst18],
            ['name' => 'Plastic Spoon', 'price' => 1.00, 'tax' => $gst5],
        ];

        foreach ($packagingMaterials as $item) {
            \App\Models\PackagingMaterial::firstOrCreate(
                ['name' => $item['name']],
                [
                    'pricing_type' => 'fixed',
                    'price_per_piece' => $item['price'],
                    'price_includes_tax' => 0,
                    'tax_slab_id' => $item['tax'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | OTHER ITEMS
        |--------------------------------------------------------------------------
        */

        $otherItems = [
            ['name' => 'Delivery Charge (Within City)', 'type' => 'fixed', 'price' => 50, 'tax' => $gst18],
            ['name' => 'Outstation Delivery Charge', 'type' => 'as_per_actual', 'price' => null, 'tax' => $gst18],
            ['name' => 'Dry Ice Charge', 'type' => 'fixed', 'price' => 100, 'tax' => $gst5],
            ['name' => 'Extra Ice Box Rental', 'type' => 'fixed', 'price' => 150, 'tax' => $gst18],
        ];

        foreach ($otherItems as $item) {
            \App\Models\OtherItem::firstOrCreate(
                ['name' => $item['name']],
                [
                    'pricing_type' => $item['type'],
                    'price_per_piece' => $item['price'],
                    'price_includes_tax' => 0,
                    'tax_slab_id' => $item['tax'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SERVICES (Catering / Event Services)
        |--------------------------------------------------------------------------
        */

        $services = [
            ['name' => 'Live Ice Cream Counter', 'type' => 'fixed', 'price' => 5000, 'tax' => $gst18],
            ['name' => 'Live Faluda Counter', 'type' => 'fixed', 'price' => 4500, 'tax' => $gst18],
            ['name' => 'Staff Service (Per Person)', 'type' => 'fixed', 'price' => 800, 'tax' => $gst18],
            ['name' => 'Event Decoration Setup', 'type' => 'as_per_actual', 'price' => null, 'tax' => $gst18],
            ['name' => 'Freezer on Rent (Per Day)', 'type' => 'fixed', 'price' => 1200, 'tax' => $gst18],
        ];

        foreach ($services as $service) {
            \App\Models\Service::firstOrCreate(
                ['name' => $service['name']],
                [
                    'pricing_type' => $service['type'],
                    'price_per_piece' => $service['price'],
                    'price_includes_tax' => 0,
                    'tax_slab_id' => $service['tax'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

    }
}