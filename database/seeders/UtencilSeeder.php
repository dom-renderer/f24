<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Utencil;

class UtencilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            'CARAT (કેરેટ)',
            'KERBA (કેરબા)',
            'ICE BOX (આઈસ બોક્સ)',
        ];

        foreach ($items as $name) {
            Utencil::updateOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
