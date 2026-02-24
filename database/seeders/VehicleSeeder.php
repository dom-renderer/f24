<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use Carbon\Carbon;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $vehicles = [
            [
                'name' => 'Tata Ace Gold (Milk Delivery)',
                'make' => 'Tata Motors',
                'number' => 'GJ01AB1001',
            ],
            [
                'name' => 'Mahindra Bolero Pickup',
                'make' => 'Mahindra',
                'number' => 'GJ01AB1002',
            ],
            [
                'name' => 'Ashok Leyland Dost',
                'make' => 'Ashok Leyland',
                'number' => 'GJ01AB1003',
            ],
            [
                'name' => 'Eicher Pro 2049 (Refrigerated)',
                'make' => 'Eicher',
                'number' => 'GJ01AB1004',
            ],
            [
                'name' => 'Force Traveller (Catering Van)',
                'make' => 'Force Motors',
                'number' => 'GJ01AB1005',
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::firstOrCreate(
                ['number' => $vehicle['number']],
                [
                    'name' => $vehicle['name'],
                    'make' => $vehicle['make'],
                    'created_by' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
