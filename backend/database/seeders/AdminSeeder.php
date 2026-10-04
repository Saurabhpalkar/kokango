<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@kokango.test'],
            [
                'name' => 'Kokango Admin',
                'password' => 'Admin@12345',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $customer = User::firstOrCreate(
            ['email' => 'rohan@example.com'],
            [
                'name' => 'Rohan Patil',
                'phone' => '9876543210',
                'password' => 'Password@123',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $customer->assignRole('customer');
    }
}
