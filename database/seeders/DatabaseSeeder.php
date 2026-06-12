<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'position' => 'HR Manager',
            ]
        );

        User::updateOrCreate(
            ['email' => 'john@example.com'],
            [
                'name' => 'John Employee',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'position' => 'Software Developer',
            ]
        );
    }
}
