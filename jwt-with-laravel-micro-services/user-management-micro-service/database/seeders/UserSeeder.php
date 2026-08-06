<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate(); // Clear existing users before seeding
        User::factory()->count(1)->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'username' => 'admin',
            "email_verified_at" => now(),
            'password' => Hash::make('admin'),
        ]);

        User::factory()->count(10)->create(); // Create 10 random users
    }
}
