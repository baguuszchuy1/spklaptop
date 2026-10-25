<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ganti email & password ini setelah login pertama kali demi keamanan.
        User::updateOrCreate(
            ['email' => 'admin@spk-laptop.test'],
            [
                'name' => 'Admin SPK',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
