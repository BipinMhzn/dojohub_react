<?php

namespace Database\Seeders;

use App\System\User\Database\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->firstOrCreate([
            'email' => 'superuser@dojohub.com',
        ], [
            'name' => 'Superuser',
            'password' => Hash::make('password'),
        ]);
    }
}
