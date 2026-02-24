<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $units = [
            'bag',
            'bori',
            'bori of 100 kg',
            'boxes',
            'Bulk',
            'Crate',
            'cup',
            'Demo',
            'gls',
            'gms',
            'kg',
            'Kgs',
            'ltr',
            'ml',
            'Nos',
            'pcs',
            'pkt',
            'tin',
        ];

        foreach ($units as $unit) {
            \App\Models\OrderUnit::updateOrCreate(['name' => $unit]);
        }
    }
}
