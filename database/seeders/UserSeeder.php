<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@securetrainingservices.co.uk',
            'password' => Hash::make('password'),
            'phone' => '0121 794 4902',
            'role_type' => 'admin',
            'status' => 1,
        ]);
    }
}
