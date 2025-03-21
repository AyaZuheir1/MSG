<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'email' => 'admin3@example.com',
            'password' => Hash::make('password123'), // يجب أن تشفري كلمة المرور
            'role' => 'admin', // حقل الدور، تأكدي أن لديك حقل role في جدول users
        ]);
    }
}
