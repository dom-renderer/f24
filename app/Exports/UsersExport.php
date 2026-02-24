<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;
use Spatie\Permission\Models\Role;
use App\Models\Designation;
use App\Helpers\Helper;
use App\Models\Store;
use App\Models\User;

class UsersExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $data = [];

        $helperRoles = Helper::$rolesKeys;
        $rolesOtherThanSystem = Role::whereNotIn('id', array_keys($helperRoles))
        ->pluck('name', 'id')
        ->map(fn($name) => \Illuminate\Support\Str::slug($name))
        ->toArray();

        if (!empty($rolesOtherThanSystem)) {
            $helperRoles = $helperRoles + $rolesOtherThanSystem;
        }

        foreach (User::get() as $user) {
            $thisRole = $user->roles[0]->id;

            $data[] = [
                $user->name,
                $user->middle_name,
                $user->last_name,
                $user->email,
                $user->employee_id,
                $user->username,
                $user->phone_number,
                $user->status == 1 ? 'enable' : 'disable',
                '',
                isset($helperRoles[$thisRole]) ? $helperRoles[$thisRole] : null
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'first name',
            'middle name',
            'last name',
            'email',
            'employee id',
            'username',
            'phone number',
            'status',
            'password',
            'role'
        ];
    }
}
