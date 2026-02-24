<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $storeTypes = [
            ['name' => 'store'],
            ['name' => 'factory'],
            ['name' => 'dealer-location']
        ];

        foreach ($storeTypes as $st) {
            \App\Models\StoreType::updateOrCreate($st);
        }

        $storeModels = [
            ['name' => 'company'],
            ['name' => 'franchisee']
        ];

        foreach ($storeModels as $sm) {
            \App\Models\ModelType::updateOrCreate($sm);
        }

        $stores = [
            [
                'name' => 'Factory',
                'code' => 'FACTORY_1',
                'store_type' => 1,
                'model_type' => 1,
                'address1' => '',
                'address2' => '',
                'block' => '',
                'street' => '',
                'landmark' => '',
                'city' => 333,
                'gst_in' => ''
            ],
            [
                'name' => 'Drive in Branch',
                'code' => 1001,
                'store_type' => 1,
                'model_type' => 1,
                'address1' => 'KAIROS BUILDING',
                'address2' => 'OPP.MAHATAMA GANDHI LABOR INSTITUTE',
                'block' => '',
                'street' => 'GURUKULROAD',
                'landmark' => 'DRIVE-IN',
                'city' => 333,
                'gst_in' => '24AADFC0032F1ZB'
            ],
            [
                'name' => 'Maa Dairy and Foods',
                'code' => 5001,
                'store_type' => 3,
                'model_type' => 1,
                'address1' => 'Safal Arise',
                'address2' => 'Nr Deep Chambers, B/s. Yes Bank',
                'block' => 'Shop No.2',
                'street' => 'Char Rasta',
                'landmark' => 'Char Rasta',
                'city' => 406,
                'gst_in' => '24ABKFM9661J1ZQ',
                'pan' => 'ABKFM9661J'
            ]
        ];

        foreach ($stores as $store) {
            \App\Models\Store::firstOrCreate(['code' => $store['code']], $store);
        }
    }
}
