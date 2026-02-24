<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'Admin',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Factory Manager',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Store Manager',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Store Employee',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Dealer',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Driver',
                'guard_name' => 'web'
            ]
        ];

        foreach ($roles as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate($role);
        }
    }
}
