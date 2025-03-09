<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'username' => 'Saja Herz',
            'email' => 'herezsaja2020@gmail.com',
            'password' => Hash::make('password123'), // تأكد من تغيير الباسورد لاحقًا
            'role' => 'doctor',
        ]);
    
        // ربط المستخدم بجدول الأطباء
        Doctor::create([
            'user_id' => $user->id,
            'first_name' => 'Saja',
            'last_name' => 'Herz',
            'major' => 'Cardiology',
            'country' => 'Germany',
            'phone_number' => '+491234567890',
            'average_rating' => 4.9,
            'image' => 'doctor_images/default.png',
            'certificate' => 'certificates/saja_certificate.pdf', 
            'gender' => 'female' 
        ]);

        // User::factory(10)->create(); // إنشاء مستخدمين
        // Doctor::factory(5)->create(); // إنشاء أطباء
        // Patient::factory(5)->create(); // إنشاء مرضى
        // Appointment::factory(20)->create(); 
    }
}
