<?php

namespace Database\Seeders;

use App\System\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'superuser@dojohub.com'],
            [
                'name' => 'Superuser',
                'password' => Hash::make('password'),
            ]
        );

        $admin->assignRole('super_admin');
    }
}
