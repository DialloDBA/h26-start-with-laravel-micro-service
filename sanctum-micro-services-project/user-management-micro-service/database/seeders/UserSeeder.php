<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();
        
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'username' => 'admin',
            'is_admin' => 1,
            'password' => bcrypt('password'),
        ]);
        User::factory(5)->create();
    }
}
