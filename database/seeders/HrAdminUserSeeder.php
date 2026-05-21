<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class HrAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['username' => 'jlladroma'],
            [
                'name' => 'HR Admin',
                'email' => 'jlladroma@ppchris.local',
                'password' => Hash::make('jlladroma'),
                'role' => 'admin',
                'is_disabled' => false,
            ],
        );

        $role = Role::firstOrCreate([
            'name' => 'hr_admin',
            'guard_name' => 'web',
        ]);

        $user->syncRoles([$role]);
    }
}
