<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call([
        //     AdminSeeder::class, // استدعاء السيدر الخاص بالأدمن
        //     DoctorSeeder::class, 
        // ]);
        $user = User::create([
            'username' => 'aya Herz',
            'email' => 'aya@gmail.com',
            'password' => Hash::make('password123'), // تأكد من تغيير الباسورد لاحقًا
            'role' => 'admin',
            'fcm_token'=>'saa'
        ]);
    
        // ربط المستخدم بجدول الأطباء
        admin::create([
            'user_id' => $user->id,
            'first_name' => 'Saja',
            'last_name' => 'Herz',
           
            'number' => '+491234567890',
            'job_title' => 'Berlin',
            
        ]);

        // User::factory(10)->create(); // إنشاء مستخدمين
        // Doctor::factory(5)->create(); // إنشاء أطباء
        // Patient::factory(5)->create(); // إنشاء مرضى
        // Appointment::factory(20)->create(); 
    }
}
